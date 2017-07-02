<?php
namespace ManaPHP\Cli;

use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Cli\Application
 *
 * @package application
 *
 * @property \ManaPHP\Cli\ConsoleInterface $console
 * @property \ManaPHP\Cli\RouterInterface  $cliRouter
 */
class Application extends \ManaPHP\Application
{
    /**
     * @var array
     */
    protected $_args;

    /**
     * @var array
     */
    protected $_controllerAliases = [];

    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader      $loader
     * @param \ManaPHP\DiInterface $dependencyInjector
     */
    public function __construct($loader, $dependencyInjector = null)
    {
        parent::__construct($loader, $dependencyInjector);

        $this->_dependencyInjector->setShared('console', 'ManaPHP\Cli\Console');
        $this->_dependencyInjector->setShared('arguments', 'ManaPHP\Cli\Arguments');
        $this->_dependencyInjector->setShared('cliRouter', 'ManaPHP\Cli\Router');

        $this->_dependencyInjector->remove('url');
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

        $controllerClassName = null;

        foreach (['@ns.app\\Cli\\Controllers', '@ns.app\\Cli', '@ns.app', 'ManaPHP\\Cli\\Controllers'] as $prefix) {
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

    public function main()
    {
        $this->registerServices();

        exit($this->handle());
    }
}