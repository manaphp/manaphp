<?php

namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\Helper\Str;

/**
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
     * @var string
     */
    protected $_command;

    /**
     * @var string
     */
    protected $_action;

    /**
     * @var array
     */
    protected $_params;

    /**
     * @param string $keyword
     *
     * @return string|false
     */
    protected function _guessCommand($keyword)
    {
        $commands = [];
        foreach ($this->_di->getDefinitions("*Command") as $name => $definition) {
            $commands[basename($name, 'Command')] = $definition;
        }

        $guessed = [];
        foreach ($commands as $name => $className) {
            if (stripos($name, $keyword) === 0) {
                $guessed[] = $className;
            }
        }

        if (!$guessed) {
            foreach ($commands as $name => $className) {
                if (stripos($name, $keyword) !== false) {
                    $guessed[] = $className;
                }
            }
        }

        return count($guessed) === 1 ? $guessed[0] : false;
    }

    /**
     * @param string $commandName
     *
     * @return string[]
     */
    protected function _getActions($commandName)
    {
        $actions = [];

        foreach (get_class_methods($commandName) as $method) {
            if (preg_match('#^(.*)Action$#', $method, $match) === 1 && $match[1] !== 'help') {
                $actions[] = $match[1];
            }
        }

        return $actions;
    }

    /**
     * @param string $commandName
     * @param string $keyword
     *
     * @return string|false
     */
    protected function _guessAction($commandName, $keyword)
    {
        $actions = $this->_getActions($commandName);

        $guessed = [];
        foreach ($actions as $action) {
            if (stripos($action, $keyword) === 0) {
                $guessed[] = $action;
            }
        }

        if (!$guessed) {
            foreach ($actions as $action) {
                if (stripos($action, $keyword) !== false) {
                    $guessed[] = $action;
                }
            }
        }

        return count($guessed) === 1 ? $guessed[0] : false;
    }

    /**
     * @param array $args
     *
     * @return void
     */
    public function route($args)
    {
        if ($args === null) {
            $args = (array)$GLOBALS['argv'];
        }
        if (str_contains($arg1 = $args[1] ?? '', ':')) {
            $args = array_merge([$args[0]], explode(':', $arg1, 2), array_slice($args, 2));
        }

        $this->_args = $args;

        $argc = count($args);

        if ($argc === 1) {
            $command = 'help';
            $action = 'commands';
            $this->_params = [];
        } elseif ($argc <= 4 && in_array(end($this->_args), ['help', '-h', '--help'], true)) {
            $command = 'help';

            if ($argc === 2) {
                $action = 'commands';
                $this->_params = [];
            } elseif ($argc === 3) {
                $action = 'command';
                $this->_params = ['--command', $this->_args[1]];
            } elseif ($argc === 4) {
                $action = 'command';
                $this->_params = ['--command', $this->_args[1], '--action', $this->_args[2]];
            } else {
                $action = null;
                $this->_params = [];
            }
        } else {
            list(, $command, $action) = array_pad($this->_args, 3, null);

            if ($action === null) {
                $this->_params = [];
            } elseif ($action[0] === '-') {
                $action = null;
                $this->_params = array_slice($this->_args, 2);
            } else {
                $this->_params = array_slice($this->_args, 3);
            }
        }

        $this->request->parse($this->_params);

        $this->_command = $command;
        $this->_action = $action;
    }

    /**
     * @param array $args
     *
     * @return int
     */
    public function handle($args = null)
    {
        $this->route($args);

        $command = Str::camelize($this->_command);
        $action = Str::variablize($this->_action);

        if (!$definition = $this->_di->getDefinition(lcfirst($command) . 'Command')) {
            $guessed = $this->_guessCommand($command);
            if ($guessed) {
                $definition = $guessed;
                $command = basename(substr($definition, strrpos($definition, '\\')), 'Command');
            } else {
                $colored_action = lcfirst($command) . ':' . $action;
                return $this->console->error(['`:action` action is not exists', 'action' => $colored_action]);
            }
        }

        /** @var \ManaPHP\Cli\Command $instance */
        $instance = $this->getShared($definition);
        if ($action === '') {
            $actions = $this->_getActions($definition);
            if (count($actions) === 1) {
                $action = $actions[0];
            } elseif (in_array('default', $actions, true)) {
                $action = 'default';
            } else {
                return $this->handle(
                    [$this->_args[0], 'help', 'command', '--command', $this->_command, '--action', $this->_action]
                );
            }
        }

        if (!$instance->isInvokable($action)) {
            $guessed = $this->_guessAction($definition, $action);
            if (!$guessed) {
                $colored_action = lcfirst($command) . ':' . $action;
                return $this->console->error(['`:action` sub action is not exists', 'action' => $colored_action]);
            } else {
                $action = $guessed;
            }
        }

        $actionMethod = $action . 'Action';
        $this->request->completeShortNames($instance, $actionMethod);
        $r = $instance->invoke($action);

        return is_int($r) ? $r : 0;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->_args;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->_command;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }
}