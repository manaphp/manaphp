<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class ManaPHP\Cli\Controllers\HelpController
 *
 * @package ManaPHP\Cli\Controllers
 */
class HelpController extends Controller
{
    /**
     * @description list all commands
     * @return int
     */
    public function listCommand()
    {
        $controllerNames = [];
        $commands = [];

        foreach ($this->filesystem->glob('@app/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+/\w+/Controllers/(\w+)Controller)\.php$#', $file, $matches)) {
                $controllerClassName = str_replace('/', '\\', $matches[1]);
                $controllerNames[] = $matches[2];
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $commands = array_merge($commands, $this->_getCommands($controllerClassName));
            }
        }

        foreach ($this->filesystem->glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+/Controllers/(\w+)Controller)\.php$#', $file, $matches)) {
                $controllerClassName = 'ManaPHP\\' . str_replace('/', '\\', $matches[1]);
                if (in_array($matches[2], $controllerNames, true)) {
                    continue;
                }
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $commands = array_merge($commands, $this->_getCommands($controllerClassName));
            }
        }

        ksort($commands);
        foreach ($commands as $command => $description) {
            $this->console->writeLn(str_pad($command, 18, ' ') . '  ' . $description);
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
        $controller = lcfirst(basename($controllerClassName, 'Controller'));

        $commands = [];
        $rc = new \ReflectionClass($controllerClassName);
        foreach (get_class_methods($controllerClassName) as $method) {
            if (preg_match('#^(.*)Command$#', $method, $match) !== 1) {
                continue;
            }
            if ($match[1] === 'help') {
                continue;
            }

            $command = $controller . ($match[1] !== 'default' ? (':' . $match[1]) : '');

            $rm = $rc->getMethod($match[0]);
            $comment = $rm->getDocComment();
            if ($comment && preg_match('#\*\s+@description\s+(.*)#', $comment, $match) === 1) {
                $commands[$command] = $match[1];
            } else {
                $commands[$command] = '';
            }
        }

        if (count($commands) === 1) {
            $commands = [$controller => array_values($commands)[0]];
        }

        return $commands;
    }
}