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

        if ($this->alias->has('@app')) {
            foreach ($this->filesystem->glob('@app/Cli/Controllers/*Controller.php') as $file) {
                if (preg_match('#/(\w+)Controller\.php$#', $file, $matches)) {
                    $controllers[] = $matches[1];
                }
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

        if ($this->alias->has('@ns.cli')) {
            $controllerClassName = $this->alias->resolveNS('@ns.cli\\' . $controller . 'Controller');
            if (!class_exists($controllerClassName)) {
                $controllerClassName = 'ManaPHP\Cli\Controllers\\' . $controller . 'Controller';
            }
        } else {
            $controllerClassName = 'ManaPHP\Cli\Controllers\\' . $controller . 'Controller';
        }

        /** @noinspection NotOptimalIfConditionsInspection */
        if (!class_exists($controllerClassName)) {
            return [];
        }

        foreach (get_class_methods($controllerClassName) as $method) {
            if (preg_match('#^(.*)Command$#', $method, $match) === 1 && $match[1] !== 'help') {
                $commands[] = $match[1];
            }
        }

        return $commands;
    }

    /**
     * @param array $args
     *
     * @return bool
     */
    public function route($args)
    {
        $this->_controllerName = null;
        $this->_actionName = null;

        if (count($args) === 2 && $args[1][0] === '/') {
            $path = parse_url($args[1], PHP_URL_PATH);
            $parts = explode('/', trim($path, '/'));
            if (count($parts) !== 2) {
                return false;
            }

            list($controllerName, $actionName) = $parts;
            $this->_controllerName = Text::camelize($controllerName);
        } else {
            list(, $controllerName, $actionName) = array_pad($args, 3, null);
            if ($controllerName === null) {
                $controllerName = 'help';
            }

            if ($actionName === null) {
                $actionName = $controllerName === 'help' ? 'list' : 'help';
            }

            if ($this->_guessCommand && strlen($controllerName) <= 4) {
                $controllers = $this->_getControllers();
                foreach ($controllers as $k => $controller) {
                    $controllers[$k] = Text::underscore($controller);
                }

                $controllerName = $this->crossword->guess($controllers, $controllerName);
                if (!$controllerName) {
                    return false;
                }
            }
            $this->_controllerName = Text::camelize($controllerName);

            if ($actionName === 'help') {
                null;
            } elseif ($actionName === null) {
                $commands = $this->_getCommands($this->_controllerName);
                if (count($commands) === 1) {
                    $actionName = $commands[0];
                } else {
                    $actionName = 'help';
                }
            } else {
                if ($this->_guessCommand) {
                    $commands = $this->_getCommands($this->_controllerName);
                    $actionName = $this->crossword->guess($commands, $actionName);
                    if (!$actionName) {
                        return false;
                    }
                }
            }
        }

        $this->_actionName = lcfirst(Text::camelize($actionName));

        return true;
    }
}