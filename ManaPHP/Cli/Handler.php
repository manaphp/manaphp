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
     * @return array
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
     * @return int
     */
    public function handle($args = null)
    {
        $this->_args = $args ?? $GLOBALS['argv'];

        list(, $commandName, $actionName) = array_pad($this->_args, 3, null);

        if ($actionName === null) {
            $this->request->parse([]);
        } elseif ($actionName[0] === '-') {
            $this->request->parse(array_splice($this->_args, 2));
        } else {
            $this->request->parse(array_splice($this->_args, 3));
        }

        if ($actionName !== null && $actionName[0] === '-') {
            $actionName = null;
        }

        if ($commandName === null) {
            $commandName = 'help';
        } elseif ($commandName === '--help' || $commandName === '-h') {
            $commandName = 'help';
            $actionName = 'list';
        } elseif ($commandName === 'help' && $actionName !== null && $actionName !== 'list') {
            $commandName = $actionName;
            $actionName = 'help';
        }

        $commandName = Str::camelize($commandName);
        $actionName = lcfirst(Str::camelize($actionName));

        if (!$command = $this->_di->getDefinition(lcfirst($commandName) . 'Command')) {
            $guessed = $this->_guessCommand($commandName);
            if ($guessed) {
                $command = $guessed;
                $commandName = basename(substr($command, strrpos($command, '\\')), 'Command');
            } else {
                $action = lcfirst($commandName) . ':' . $actionName;
                return $this->console->error(['`:action` action is not exists', 'action' => $action]);
            }
        }

        /** @var \ManaPHP\Cli\Command $instance */
        $instance = $this->getShared($command);
        if ($actionName === '') {
            $actions = $this->_getActions($command);
            if (count($actions) === 1) {
                $actionName = $actions[0];
            } elseif (in_array('default', $actions, true)) {
                $actionName = 'default';
            } else {
                $actionName = 'help';
            }
        }

        if ($actionName !== 'help' && $this->request->has('help')) {
            $actionName = 'help';
        }

        if (!$instance->isInvokable($actionName)) {
            $guessed = $this->_guessAction($command, $actionName);
            if (!$guessed) {
                $action = lcfirst($commandName) . ':' . $actionName;
                return $this->console->error(['`:action` sub action is not exists', 'action' => $action]);
            } else {
                $actionName = $guessed;
            }
        }

        $actionMethod = $actionName . 'Action';
        $this->request->completeShortNames($instance, $actionMethod);
        $r = $instance->invoke($actionName);

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