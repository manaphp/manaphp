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
     * list all actions
     *
     * @return int
     */
    public function listAction()
    {
        $this->console->writeLn('manaphp commands:', Console::FC_GREEN | Console::AT_BOLD);
        foreach (glob(__DIR__ . '/*Controller.php') as $file) {
            $plainName = basename($file, '.php');
            $controller = Str::underscore(basename($plainName, 'Controller'));
            $this->console->writeLn(' - ' . $this->console->colorize($controller, Console::FC_YELLOW));
            $actions = $this->_getActions(__NAMESPACE__ . "\\" . $plainName);

            $width = max(max(array_map('strlen', array_keys($actions))), 18);
            foreach ($actions as $action => $description) {
                $colored_action = $this->console->colorize($action, Console::FC_CYAN, $width);
                $this->console->writeLn('    ' . $colored_action . ' ' . $description);
            }
        }

        if ($this->alias->has('@cli')) {
            $this->console->writeLn('application commands: ', Console::FC_GREEN | Console::AT_BOLD);

            foreach (glob($this->alias->resolve('@cli/*Controller.php')) as $file) {
                $plainName = basename($file, '.php');
                $controller = Str::underscore(basename($plainName, 'Controller'));
                $this->console->writeLn(' - ' . $this->console->colorize($controller, Console::FC_YELLOW));

                $actions = $this->_getActions($this->alias->resolveNS("@ns.cli\\$plainName"));

                $width = max(max(array_map('strlen', array_keys($actions))), 18);
                foreach ($actions as $action => $description) {
                    $colored_action = $this->console->colorize($action, Console::FC_CYAN, $width + 1);
                    $this->console->writeLn("    $colored_action $description");
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
    protected function _getActions($controllerClassName)
    {
        $controller = Str::underscore(basename(strtr($controllerClassName, '\\', '/'), 'Controller'));

        $actions = [];
        $rc = new ReflectionClass($controllerClassName);
        foreach (get_class_methods($controllerClassName) as $method) {
            if (preg_match('#^(.*)Action$#', $method, $match) !== 1) {
                continue;
            }
            if ($match[1] === 'help') {
                continue;
            }

            $action = $controller . ($match[1] === 'default' ? '' : (' ' . $match[1]));

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
            $actions[$action] = $description;
        }

        ksort($actions);

        return $actions;
    }
}