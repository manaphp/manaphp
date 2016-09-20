<?php
namespace ManaPHP\Cli;

use ManaPHP\Component;

class Router extends Component implements RouterInterface
{
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
     * @param string $cmd
     *
     * @return bool
     */
    public function handle($cmd)
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
                $this->_controllerName = $parts[0];
                $this->_actionName = 'default';
                return true;
            case 2:
                $this->_controllerName = $parts[0];
                $this->_actionName = $parts[1];
                return true;
        }

        return false;
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