<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Console;
use ManaPHP\Cli\Controller;
use ManaPHP\Helper\Str;
use ReflectionClass;

/**
 * Class ManaPHP\Cli\Controllers\HelpController
 *
 * @package ManaPHP\Cli\Controllers
 */
class HelpController extends Controller
{
    /**
     * list all commands
     *
     * @return int
     */
    public function listCommand()
    {
        $this->console->writeLn('manaphp commands:', Console::FC_GREEN | Console::AT_BOLD);
        foreach (glob(__DIR__ . '/*Controller.php') as $file) {
            $plainName = basename($file, '.php');
            $this->console->writeLn(' - ' . $this->console->colorize(Str::underscore(basename($plainName, 'Controller')), Console::FC_YELLOW));
            $commands = $this->_getCommands(__NAMESPACE__ . "\\" . $plainName);

            $width = max(max(array_map('strlen', array_keys($commands))), 18);
            foreach ($commands as $command => $description) {
                $this->console->writeLn('    ' . $this->console->colorize($command, Console::FC_CYAN, $width) . ' ' . $description);
            }
        }

        if ($this->alias->has('@cli')) {
            $this->console->writeLn('application commands: ', Console::FC_GREEN | Console::AT_BOLD);

            foreach (glob($this->alias->resolve('@cli/*Controller.php')) as $file) {
                $plainName = basename($file, '.php');
                $this->console->writeLn(' - ' . $this->console->colorize(Str::underscore(basename($plainName, 'Controller')), Console::FC_YELLOW));

                $commands = $this->_getCommands($this->alias->resolveNS("@ns.cli\\$plainName"));

                $width = max(max(array_map('strlen', array_keys($commands))), 18);
                foreach ($commands as $command => $description) {
                    $this->console->writeLn('    ' . $this->console->colorize($command, Console::FC_CYAN, $width + 1) . ' ' . $description);
                }
            }
        }

        return 0;
    }

    /**
     * @param string $controllerClassName
     *
     * @return array
     */
    protected function _getCommands($controllerClassName)
    {
        $controller = Str::underscore(basename(strtr($controllerClassName, '\\', '/'), 'Controller'));

        $commands = [];
        $rc = new ReflectionClass($controllerClassName);
        foreach (get_class_methods($controllerClassName) as $method) {
            if (preg_match('#^(.*)Command$#', $method, $match) !== 1) {
                continue;
            }
            if ($match[1] === 'help') {
                continue;
            }

            $command = $controller . ($match[1] === 'default' ? '' : (' ' . $match[1]));

            $description = '';
            foreach (preg_split('#[\r\n]+#', $rc->getMethod($match[0])->getDocComment()) as $line) {
                $line = trim($line, "\t /*\r\n");
                if (!$line) {
                    continue;
                }

                if ($line[0] !== '@') {
                    $description = $line;
                }
                break;
            }
            $commands[$command] = $description;
        }

        ksort($commands);

        return $commands;
    }
}