<?php
namespace ManaPHP\Cli;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Utility\Text;

/**
 * Class Application
 *
 * @package ManaPHP\Cli
 *
 * @property \ManaPHP\Cli\ConsoleInterface $console
 * @property \ManaPHP\Cli\RouterInterface  $cliRouter
 *
 */
abstract class Application extends \ManaPHP\Application
{
    /**
     * @var array
     */
    protected $_args;

    /**
     * @var array
     */
    protected $_controllerAliases = [];

    public function __construct($dependencyInjector = null)
    {
        $this->_dependencyInjector = $dependencyInjector ?: new FactoryDefault();

        $this->_dependencyInjector->setShared('application', $this);
        $this->_dependencyInjector->setShared('console', 'ManaPHP\Cli\Console');
        $this->_dependencyInjector->setShared('arguments', 'ManaPHP\Cli\Arguments');
        $this->_dependencyInjector->setShared('cliRouter', 'ManaPHP\Cli\Router');
    }

    /**
     * @param array $args
     *
     * @return int
     * @throws \ManaPHP\Cli\Application\Exception
     */
    public function handle($args = null)
    {
        $this->_args = $args ?: $GLOBALS['argv'];

        $command = count($this->_args) === 1 ? null : $this->_args[1];
        if (!$this->cliRouter->route($command)) {
            $this->console->writeLn('command name is invalid: ' . $command);
            return 1;
        }

        $controllerName = $this->cliRouter->getControllerName();
        $actionName = lcfirst($this->cliRouter->getActionName());

        $this->console->writeLn('executed command is `' . Text::underscore($controllerName) . ':' . Text::underscore($actionName) . '`');

        $controllerClassName = null;
        foreach (['Application\\Cli\\Controllers\\' . $controllerName . 'Controller', 'ManaPHP\\Cli\\Controllers\\' . $controllerName . 'Controller'] as $class) {
            if ($this->_dependencyInjector->has($class) || class_exists($class)) {
                $controllerClassName = $class;
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