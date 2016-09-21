<?php
namespace ManaPHP\Cli;

use ManaPHP\Component;

/**
 * Class Router
 *
 * @package ManaPHP\Cli
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
     * @var array
     */
    protected $_commandAliases = [];

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
            if (preg_match('#/(\w+/\w+/Controllers/(\w+)Controller)\.php$#', $file, $matches)) {
                $controllers[$matches[2]] = str_replace('/', '\\', $matches[1]);
            }
        }

        foreach ($this->filesystem->glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+/\w+/Controllers/(\w+)Controller)\.php$#', $file, $matches)) {
                if (in_array($matches[2], $controllers, true)) {
                    continue;
                }

                $controllers[$matches[2]] = str_replace('/', '\\', $matches[1]);
            }
        }

        return $controllers;
    }

    protected function _getCommands($controller)
    {
        $commands = [];

        foreach (get_class_methods($controller) as $method) {
            if (preg_match('#^(.*)Command$#', $method, $match) === 1) {
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

        if (isset($this->_commandAliases[strtolower($command)])) {
            $command = $this->_commandAliases[strtolower($command)];
        }

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

        if ($this->_guessCommand) {
            $controllers = $this->_getControllers();
            $controllerName = $this->crossword->guess(array_keys($controllers), $controllerName);
            if (!$controllerName) {
                return false;
            }
            $commands = $this->_getCommands($controllers[$controllerName]);
            if ($actionName === null && count($commands) === 1) {
                $actionName = $commands[0];
            } else {
                $actionName = $this->crossword->guess($commands, $actionName);
                if (!$actionName) {
                    return false;
                }
            }
        } else {
            $actionName = $actionName ?: 'default';
        }

        $this->_controllerName = $controllerName;
        $this->_actionName = $actionName;
        return true;
    }

    /**
     * @param string $alias
     * @param string $command
     *
     * @return static
     */
    public function setAlias($alias, $command)
    {
        $this->_commandAliases[$alias] = $command;

        return $this;
    }
}