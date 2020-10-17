<?php

namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

/**
 * Class Handler
 *
 * @package ManaPHP\Cli
 *
 * @property-read \ManaPHP\Cli\ConsoleInterface $console
 * @property-read \ManaPHP\Cli\RequestInterface $request
 */
class Handler extends Component implements HandlerInterface
{
    /**
     * @var array
     */
    protected $_args;

    /**
     * @param string $keyword
     *
     * @return string|false
     */
    protected function _guessController($keyword)
    {
        $controllers = [];

        foreach (LocalFS::glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+)Controller\.php$#', $file, $matches)) {
                $controllers[$matches[1]] = "ManaPHP\\Cli\Controllers\\{$matches[1]}Controller";
            }
        }

        if ($this->alias->has('@cli')) {
            foreach (LocalFS::glob('@cli/*Controller.php') as $file) {
                if (preg_match('#/(\w+)Controller\.php$#', $file, $matches)) {
                    $controllers[$matches[1]] = $this->alias->resolveNS("@ns.cli\\{$matches[1]}Controller");
                }
            }
        }

        $guessed = [];
        foreach ($controllers as $name => $className) {
            if (stripos($name, $keyword) === 0) {
                $guessed[] = $className;
            }
        }

        if (!$guessed) {
            foreach ($controllers as $name => $className) {
                if (stripos($name, $keyword) !== false) {
                    $guessed[] = $className;
                }
            }
        }

        return count($guessed) === 1 ? $guessed[0] : false;
    }

    /**
     * @param string $controllerName
     *
     * @return array
     */
    protected function _getCommands($controllerName)
    {
        $commands = [];

        foreach (get_class_methods($controllerName) as $method) {
            if (preg_match('#^(.*)Command$#', $method, $match) === 1 && $match[1] !== 'help') {
                $commands[] = $match[1];
            }
        }

        return $commands;
    }

    /**
     * @param string $controllerName
     * @param string $keyword
     *
     * @return string|false
     */
    protected function _guessCommand($controllerName, $keyword)
    {
        $commands = $this->_getCommands($controllerName);

        $guessed = [];
        foreach ($commands as $command) {
            if (stripos($command, $keyword) === 0) {
                $guessed[] = $command;
            }
        }

        if (!$guessed) {
            foreach ($commands as $command) {
                if (stripos($command, $keyword) !== false) {
                    $guessed[] = $command;
                }
            }
        }

        return count($guessed) === 1 ? $guessed[0] : false;
    }

    /**
     * @param array $args
     *
     * @return int
     */
    public function handle($args = null)
    {
        $this->_args = $args ?? $GLOBALS['argv'];

        list(, $controllerName, $commandName) = array_pad($this->_args, 3, null);

        if ($commandName === null) {
            $this->request->parse([]);
        } elseif ($commandName[0] === '-') {
            $this->request->parse(array_splice($this->_args, 2));
        } else {
            $this->request->parse(array_splice($this->_args, 3));
        }

        if ($commandName !== null && $commandName[0] === '-') {
            $commandName = null;
        }

        if ($controllerName === 'list') {
            $controllerName = 'help';
            $commandName = $commandName ?: 'list';
        } elseif ($controllerName === null) {
            $controllerName = 'help';
        } elseif ($controllerName === '--help' || $controllerName === '-h') {
            $controllerName = 'help';
            $commandName = 'list';
        } elseif ($controllerName === 'help' && $commandName !== null && $commandName !== 'list') {
            $controllerName = $commandName;
            $commandName = 'help';
        }

        $controllerName = Str::camelize($controllerName);
        $commandName = lcfirst(Str::camelize($commandName));

        $controller = null;

        if ($this->alias->has('@ns.cli')) {
            $namespaces = ['@ns.cli', 'ManaPHP\\Cli\\Controllers'];
        } else {
            $namespaces = ['ManaPHP\\Cli\\Controllers'];
        }

        foreach ($namespaces as $prefix) {
            $className = $this->alias->resolveNS($prefix . '\\' . $controllerName . 'Controller');

            if (class_exists($className)) {
                $controller = $className;
                break;
            }
        }

        if (!$controller) {
            $guessed = $this->_guessController($controllerName);
            if ($guessed) {
                $controller = $guessed;
                $controllerName = basename(substr($controller, strrpos($controller, '\\')), 'Controller');
            } else {
                $command = lcfirst($controllerName) . ':' . $commandName;
                return $this->console->error(['`:command` command is not exists', 'command' => $command]);
            }
        }

        /** @var \ManaPHP\Controller $instance */
        $instance = $this->getShared($controller);
        if ($commandName === '') {
            $commands = $this->_getCommands($controller);
            if (count($commands) === 1) {
                $commandName = $commands[0];
            } elseif (in_array('default', $commands, true)) {
                $commandName = 'default';
            } else {
                $commandName = 'help';
            }
        }

        if ($commandName !== 'help' && $this->request->has('help')) {
            $commandName = 'help';
        }

        if (!$instance->isInvokable($commandName)) {
            $guessed = $this->_guessCommand($controller, $commandName);
            if (!$guessed) {
                $command = lcfirst($controllerName) . ':' . $commandName;
                return $this->console->error(['`:command` sub command is not exists', 'command' => $command]);
            } else {
                $commandName = $guessed;
            }
        }

        $commandMethod = $commandName . 'Command';
        $this->request->completeShortNames($instance, $commandMethod);
        $r = $instance->invoke($commandName);

        return is_int($r) ? $r : 0;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->_args;
    }
}