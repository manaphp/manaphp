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
    protected $args;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var array
     */
    protected $params;

    /**
     * @param string $keyword
     *
     * @return string|false
     */
    protected function guessCommand($keyword)
    {
        $commands = [];
        foreach ($this->container->getDefinitions("*Command") as $name => $definition) {
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
    protected function getActions($commandName)
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
    protected function guessAction($commandName, $keyword)
    {
        $actions = $this->getActions($commandName);

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

        $this->args = $args;

        $argc = count($args);

        if ($argc === 1) {
            $command = 'help';
            $action = 'commands';
            $this->params = [];
        } elseif ($argc <= 4 && in_array(end($this->args), ['help', '-h', '--help'], true)) {
            $command = 'help';

            if ($argc === 2) {
                $action = 'commands';
                $this->params = [];
            } elseif ($argc === 3) {
                $action = 'command';
                $this->params = ['--command', $this->args[1]];
            } elseif ($argc === 4) {
                $action = 'command';
                $this->params = ['--command', $this->args[1], '--action', $this->args[2]];
            } else {
                $action = null;
                $this->params = [];
            }
        } else {
            list(, $command, $action) = array_pad($this->args, 3, null);

            if ($action === null) {
                $this->params = [];
            } elseif ($action[0] === '-') {
                $action = null;
                $this->params = array_slice($this->args, 2);
            } else {
                $this->params = array_slice($this->args, 3);
            }
        }

        $this->request->parse($this->params);

        $this->command = $command;
        $this->action = $action;
    }

    /**
     * @param array $args
     *
     * @return int
     */
    public function handle($args = null)
    {
        $this->route($args);

        $command = Str::pascalize($this->command);
        $action = Str::camelize($this->action);

        if (!$definition = $this->container->getDefinition(lcfirst($command) . 'Command')) {
            $guessed = $this->guessCommand($command);
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
            $actions = $this->getActions($definition);
            if (count($actions) === 1) {
                $action = $actions[0];
            } elseif (in_array('default', $actions, true)) {
                $action = 'default';
            } else {
                return $this->handle(
                    [$this->args[0], 'help', 'command', '--command', $this->command, '--action', $this->action]
                );
            }
        }

        if (!$instance->isInvokable($action)) {
            $guessed = $this->guessAction($definition, $action);
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

        return is_int($r) ? $r : $this->console->error($r);
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}