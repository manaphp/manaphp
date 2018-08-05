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
 * @property \ManaPHP\Cli\ConsoleInterface         $console
 * @property \ManaPHP\Cli\Command\InvokerInterface $commandInvoker
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

        foreach ($this->filesystem->glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
            if (preg_match('#/(\w+)Controller\.php$#', $file, $matches)) {
                $controllers[$matches[1]] = "ManaPHP\\Cli\Controllers\\{$matches[1]}Controller";
            }
        }

        if ($this->alias->has('@cli')) {
            foreach ($this->filesystem->glob('@cli/*Controller.php') as $file) {
                if (preg_match('#/(\w+)Controller\.php$#', $file, $matches)) {
                    $controllers[$matches[1]] = $this->alias->resolve("@ns.cli\\{$matches[1]}Controller");
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
        $this->_args = $args !== null ? $args : $GLOBALS['argv'];

        list(, $controllerName, $commandName) = array_pad($this->_args, 3, null);
        if ($commandName !== null && $commandName[0] === '-') {
            $commandName = null;
        }

        if ($controllerName === 'list') {
            $controllerName = 'help';
            $commandName = $commandName ?: 'list';
        } elseif ($controllerName === null) {
            $controllerName = 'help';
        } elseif ($controllerName === 'help' && $commandName !== null && $commandName !== 'list') {
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
            $guessed = $this->_guessController($controllerName);
            if ($guessed) {
                $controllerClassName = $guessed;
                $controllerName = basename(substr($controllerClassName, strrpos($controllerClassName, '\\')), 'Controller');
            } else {
                return $this->console->error(['`:command` command is not exists', 'command' => lcfirst($controllerName) . ':' . $commandName]);
            }
        }

        $controllerInstance = $this->_di->getShared($controllerClassName);
        if ($commandName === '') {
            $commands = $this->_getCommands($controllerClassName);
            if (count($commands) === 1) {
                $commandName = $commands[0];
            } elseif (in_array('default', $commands, true)) {
                $commandName = 'default';
            } else {
                $commandName = 'help';
            }
        }

        if ($commandName !== 'help' && in_array('--help', $this->_args, true)) {
            $commandName = 'help';
        }

        if (!method_exists($controllerInstance, $commandName . 'Command')) {
            $guessed = $this->_guessCommand($controllerClassName, $commandName);
            if (!$guessed) {
                return $this->console->error(['`:command` sub command is not exists', 'command' => lcfirst($controllerName) . ':' . $commandName]);
            } else {
                $commandName = $guessed;
            }
        }

        try {
            $r = $this->commandInvoker->invoke($controllerInstance, $commandName);
        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch (ArgumentsException $e) {
            return $this->console->error($e->getMessage());
        }

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