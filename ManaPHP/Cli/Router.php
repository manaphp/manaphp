<?php

namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Cli\Router
 *
 * @package router
 *
 * @property \ManaPHP\Text\CrosswordInterface $crossword
 */
class Router extends Component implements RouterInterface
{
    /**
     * @var bool
     */
    protected $_guessCommand = true;

    /**
     * @var string
     */
    protected $_controllerName;

    /**
     * @var string
     */
    protected $_actionName;

    /**
     * @return string
     */
    public function getControllerName()
    {
        return $this->_controllerName;
    }

    /**
     * @return string
     */
    public function getActionName()
    {
        return $this->_actionName;
    }

    /**
     * @return array
     */
    protected function _getControllers()
    {
        $controllers = [];

        foreach ($this->filesystem->glob('@app/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+)Controller\.php$#', $file, $matches)) {
                $controllers[] = $matches[1];
            }
        }

        foreach ($this->filesystem->glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+)Controller\.php$#', $file, $matches)) {
                if (in_array($matches[1], $controllers, true)) {
                    continue;
                }

                $controllers[] = $matches[1];
            }
        }

        return $controllers;
    }

    /**
     * @param string $controller
     *
     * @return array
     */
    protected function _getCommands($controller)
    {
        $commands = [];

        $controllerClassName = $this->alias->resolveNS('@ns.app\\Cli\Controllers\\' . $controller . 'Controller');
        if (!class_exists($controllerClassName)) {
            $controllerClassName = 'ManaPHP\Cli\Controllers\\' . $controller . 'Controller';
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!class_exists($controllerClassName)) {
                return [];
            }
        }

        foreach (get_class_methods($controllerClassName) as $method) {
            if (preg_match('#^(.*)Command$#', $method, $match) === 1 && $match[1] !== 'help') {
                $commands[] = $match[1];
            }
        }

        return $commands;
    }

    /**
     * @param string $cmd
     *
     * @return bool
     */
    public function route($cmd)
    {
        $this->_controllerName = null;
        $this->_actionName = null;

        $command = $cmd ?: 'help:list';

        $parts = explode(':', $command);
        switch (count($parts)) {
            case 1:
                $controllerName = $parts[0];
                $actionName = null;
                break;
            case 2:
                $controllerName = $parts[0];
                $actionName = $parts[1];
                break;
            default:
                return false;
        }

        if ($this->_guessCommand && strlen($controllerName) <= 4) {
            $controllers = $this->_getControllers();
            $controllerName = $this->crossword->guess($controllers, $controllerName);
            if (!$controllerName) {
                return false;
            }
        } else {
            $controllerName = Text::camelize($controllerName);
        }

        if ($actionName === null) {
            $commands = $this->_getCommands($controllerName);
            if (count($commands) === 1) {
                $actionName = $commands[0];
            } else {
                $actionName = 'help';
            }
        } else {
            if ($this->_guessCommand && strlen($actionName) <= 2) {
                $commands = $this->_getCommands($controllerName);
                $actionName = $this->crossword->guess($commands, $actionName);
                if (!$actionName) {
                    return false;
                }
            } else {
                $actionName = lcfirst(Text::camelize($actionName));
            }
        }

        $this->_controllerName = $controllerName;
        $this->_actionName = $actionName;
        return true;
    }
}