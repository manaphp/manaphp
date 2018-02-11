<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Console;
use ManaPHP\Cli\Controller;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Cli\Controllers\HelpController
 *
 * @package ManaPHP\Cli\Controllers
 */
class HelpController extends Controller
{
    /**
     * @CliCommand list all commands
     * @return int
     */
    public function listCommand()
    {
        $this->console->writeLn('manaphp commands:', Console::FC_GREEN | Console::AT_BOLD);
        foreach ($this->filesystem->glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+/Controllers/(\w+)Controller)\.php$#', $file, $matches)) {
                $controllerClassName = 'ManaPHP\\' . strtr($matches[1], '/', '\\');

                $commands = $this->_getCommands($controllerClassName);
                ksort($commands);

                if (!$commands) {
                    continue;
                }

                $first = true;
                $maxLength = max(max(array_map('strlen', array_keys($commands))), 16);
                foreach ($commands as $command => $description) {
                    $cmd = str_pad($command, $maxLength + 1);
                    $this->console->writeLn('  ' . (!$first ? $cmd : $this->console->colorize($cmd, Console::FC_CYAN)) . ' ' . $description);
                    $first = false;
                }
            }
        }

        $this->console->writeLn('application commands: ', Console::FC_GREEN | Console::AT_BOLD);
        if ($this->alias->has('@cli')) {
            foreach ($this->filesystem->glob('@cli/*Controller.php') as $file) {
                if (preg_match('#(\w+)Controller\.php$#', $file, $matches)) {
                    $controllerClassName = $this->alias->resolveNS('@ns.cli\\' . $matches[1] . 'Controller');
                    $commands = $this->_getCommands($controllerClassName);

                    ksort($commands);

                    if (!$commands) {
                        continue;
                    }

                    $first = true;
                    $maxLength = max(max(array_map('strlen', array_keys($commands))), 16);
                    foreach ($commands as $command => $description) {
                        $cmd = str_pad($command, $maxLength + 1);
                        $this->console->writeLn('  ' . (!$first ? $cmd : $this->console->colorize($cmd, Console::FC_CYAN)) . ' ' . $description);
                        $first = false;
                    }
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
        $controller = Text::underscore(basename(strtr($controllerClassName, '\\', '/'), 'Controller'));

        $commands = [];
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $rc = new \ReflectionClass($controllerClassName);
        foreach (get_class_methods($controllerClassName) as $method) {
            if (preg_match('#^(.*)Command$#', $method, $match) !== 1) {
                continue;
            }
            if ($match[1] === 'help') {
                continue;
            }

            $command = $controller . ' ' . $match[1];

            $rm = $rc->getMethod($match[0]);
            $comment = $rm->getDocComment();
            if ($comment && preg_match('#\*\s+@CliCommand\s+(.*)#', $comment, $match) === 1) {
                $commands[$command] = $match[1];
            } else {
                $commands[$command] = '';
            }
        }

        return $commands;
    }
}