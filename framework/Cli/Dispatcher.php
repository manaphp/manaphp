<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Cli\Command\ArgumentsResolverInterface;
use ManaPHP\Cli\Event\CliInvoked;
use ManaPHP\Cli\Event\CliInvoking;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Helper\Str;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class Dispatcher implements DispatcherInterface
{
    #[Inject] protected EventDispatcherInterface $eventDispatcher;
    #[Inject] protected ArgumentsResolverInterface $argumentsResolver;
    #[Inject] protected ConsoleInterface $console;
    #[Inject] protected ContainerInterface $container;
    #[Inject] protected RequestInterface $request;

    #[Value] protected array $commands = ['App\Commands\*Command', 'ManaPHP\Commands\*Command'];

    protected function getCommand(string $command): ?string
    {
        foreach ($this->commands as $name) {
            if (class_exists($class = str_replace('*', $command, $name))) {
                return $class;
            }
        }

        return null;
    }

    protected function getMethod(string $command, string $action): ?string
    {
        $method = $action . 'Action';
        if (method_exists($command, $method)) {
            return $method;
        }

        if ($action === 'default') {
            $methods = [];
            foreach (get_class_methods($command) as $method) {
                if (str_ends_with($method, 'Action')) {
                    $methods[] = $method;
                }
            }

            if (count($methods) === 1) {
                return $methods[0];
            }
        }

        return null;
    }

    public function dispatch(string $command, string $action, array $params): int
    {
        $command = Str::pascalize($command);
        $action = Str::camelize($action);

        $cmd = lcfirst($command) . ':' . $action;

        if (($class = $this->getCommand($command)) === null) {
            return $this->console->error("`$cmd` command is not exists");
        }

        $instance = $this->container->get($class);

        if (($method = $this->getMethod($class, $action)) === null) {
            return $this->console->error("`$cmd` action is not exists");
        }

        $this->request->completeShortNames($instance, $method);
        $this->eventDispatcher->dispatch(new CliInvoking($this, $instance, $method, $action));
        $arguments = $this->argumentsResolver->resolve($instance, $method);
        $return = $instance->$method(...$arguments);
        $this->eventDispatcher->dispatch(new CliInvoked($this, $instance, $method, $action, $return));
        if ($return === null) {
            return 0;
        } elseif (is_int($return)) {
            return $return;
        } else {
            return $this->console->error($return);
        }
    }
}