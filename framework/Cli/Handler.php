<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Cli\Command\ArgumentsResolverInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\EventTrait;
use ManaPHP\Helper\Str;
use Psr\Container\ContainerInterface;

class Handler implements HandlerInterface
{
    use EventTrait;

    #[Inject] protected ConsoleInterface $console;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected CommandManagerInterface $commandManager;
    #[Inject] protected ContainerInterface $container;
    #[Inject] protected ArgumentsResolverInterface $argumentsResolver;

    protected array $args;
    protected string $command;
    protected ?string $action = null;
    protected array $params;

    protected function getActions(string $commandName): array
    {
        $actions = [];

        foreach (get_class_methods($commandName) as $method) {
            if (preg_match('#^(.*)Action$#', $method, $match) === 1 && $match[1] !== 'help') {
                $actions[] = $match[1];
            }
        }

        return $actions;
    }

    public function route(?array $args): void
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

    public function handle(?array $args = null): int
    {
        $this->route($args);

        $command = Str::pascalize($this->command);
        $action = $this->action ? Str::camelize($this->action) : '';

        $commands = $this->commandManager->getCommands();
        if (($definition = $commands[lcfirst($command)] ?? null) === null) {
            $colored_action = lcfirst($command) . ':' . $action;
            return $this->console->error("`$colored_action` action is not exists");
        }

        $instance = $this->container->get($definition);
        if ($action === '') {
            $actions = $this->getActions($definition);
            if (count($actions) === 1) {
                $action = $actions[0];
            } elseif (in_array('default', $actions, true)) {
                $action = 'default';
            } else {
                if ($this->action === null) {
                    return $this->handle(
                        [$this->args[0], 'help', 'command', '--command', $this->command]
                    );
                } else {
                    return $this->handle(
                        [$this->args[0], 'help', 'command', '--command', $this->command, '--action', $this->action]
                    );
                }
            }
        }

        if (!method_exists($instance, $action . 'Action')) {
            $colored_action = lcfirst($command) . ':' . $action;
            return $this->console->error("`$colored_action` sub action is not exists");
        }

        $method = $action . 'Action';
        $this->request->completeShortNames($instance, $method);
        $this->fireEvent('cli:invoking', compact('instance', 'method', 'action'));
        $arguments = $this->argumentsResolver->resolve($instance, $method);
        $return = $instance->$method(...$arguments);
        $this->fireEvent('cli:invoked', compact('instance', 'method', 'action', 'return'));
        if ($return === null) {
            return 0;
        } elseif (is_int($return)) {
            return $return;
        } else {
            return $this->console->error($return);
        }
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}