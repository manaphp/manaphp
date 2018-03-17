<?php

namespace ManaPHP\Cli;

use ManaPHP\Cli\Arguments\Exception as ArgumentsException;
use ManaPHP\Component;
use ManaPHP\Utility\Text;

/**
 * Class Handler
 *
 * @package ManaPHP\Cli
 *
 * @property \ManaPHP\Cli\ConsoleInterface $console
 */
class Handler extends Component implements HandlerInterface
{
    /**
     * @var array
     */
    protected $_args;

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
     * @param array $args
     *
     * @return int
     */
    public function handle($args = null)
    {
        $this->_args = $args !== null ? $args : $GLOBALS['argv'];

        list(, $controllerName, $commandName) = array_pad($this->_args, 3, null);
        if ($controllerName === null) {
            $controllerName = 'help';
        } elseif ($controllerName === 'help' && $commandName !== null) {
            $controllerName = $commandName;
            $commandName = 'help';
        }

        $controllerName = Text::camelize($controllerName);
        $commandName = lcfirst(Text::camelize($commandName));

        $controllerClassName = null;

        if ($this->alias->has('@ns.cli')) {
            $namespaces = ['@ns.cli', 'ManaPHP\\Cli\\Controllers'];
        } else {
            $namespaces = ['ManaPHP\\Cli\\Controllers'];
        }

        foreach ($namespaces as $prefix) {
            $className = $this->alias->resolveNS($prefix . '\\' . $controllerName . 'Controller');

            if (class_exists($className)) {
                $controllerClassName = $className;
                break;
            }
        }

        if (!$controllerClassName) {
            return $this->console->error(['`:command` command is not exists'/**m0d7fa39c3a64b91e0*/, 'command' => lcfirst($controllerName) . ':' . $commandName]);
        }

        $controllerInstance = $this->_dependencyInjector->getShared($controllerClassName);
        if ($commandName === '') {
            $commands = $this->_getCommands($controllerClassName);
            if (count($commands) === 1) {
                $commandName = $commands[0];
            } elseif (in_array('default', $commands, true)) {
                $commandName = 'default';
            } else {
                return $this->console->error('[sub-command is not provided');
            }
        }

        $commandMethod = $commandName . 'Command';
        if (!method_exists($controllerInstance, $commandMethod)) {
            return $this->console->error(['`:command` sub command is not exists'/**m061a35fc1c0cd0b6f*/, 'command' => lcfirst($controllerName) . ':' . $commandName]);
        }

        try {
            $r = $controllerInstance->$commandMethod();
        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch (ArgumentsException $e) {
            return $this->console->error($e->getMessage());
        }

        return is_int($r) ? $r : 0;
    }
}