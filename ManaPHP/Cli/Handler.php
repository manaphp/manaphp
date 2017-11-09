<?php

namespace ManaPHP\Cli;

use ManaPHP\Component;

/**
 * Class Handler
 *
 * @package ManaPHP\Cli
 *
 * @property \ManaPHP\Cli\RouterInterface  $cliRouter
 * @property \ManaPHP\Cli\ConsoleInterface $console
 */
class Handler extends Component implements HandlerInterface
{
    /**
     * @var array
     */
    protected $_args;

    /**
     * @param array $args
     *
     * @return int
     */
    public function handle($args = null)
    {
        $this->_args = $args !== null ? $args : $GLOBALS['argv'];

        if (!$this->cliRouter->route($this->_args)) {
            $this->console->writeLn('command name is invalid: ' . implode(' ', $this->_args));
            return 1;
        }

        $controllerName = $this->cliRouter->getControllerName();
        $actionName = lcfirst($this->cliRouter->getActionName());

        $controllerClassName = null;

        foreach (['@ns.cli', 'ManaPHP\\Cli\\Controllers'] as $prefix) {
            $class = $this->alias->resolveNS($prefix . '\\' . $controllerName . 'Controller');

            if (class_exists($class)) {
                $controllerClassName = $class;
                break;
            }
        }

        if (!$controllerClassName) {
            $this->console->writeLn('``:command` command is not exists'/**m0d7fa39c3a64b91e0*/, ['command' => lcfirst($controllerName) . ':' . $actionName]);
            return 1;
        }

        $controllerInstance = $this->_dependencyInjector->getShared($controllerClassName);

        $actionMethod = $actionName . 'Command';
        if (!method_exists($controllerInstance, $actionMethod)) {
            $this->console->writeLn('`:command` sub command is not exists'/**m061a35fc1c0cd0b6f*/, ['command' => lcfirst($controllerName) . ':' . $actionName]);
            return 1;
        }

        $r = $controllerInstance->$actionMethod();

        return is_int($r) ? $r : 0;
    }
}