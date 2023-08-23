<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\Attribute\Event;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionUnionType;
use SplDoublyLinkedList;

class ListenerProvider implements ListenerProviderInterface
{
    protected const PEEKER = '*';

    protected array $listeners = [];

    #[Inject] protected ContainerInterface $container;

    public function getListenersForEvent(object $event): iterable
    {
        return $this->listeners[$event::class] ?? [];
    }

    public function getListenersForPeeker(): iterable
    {
        return $this->listeners[self::PEEKER] ?? [];
    }

    public function on(string $event, callable $handler, int $priority = 0): void
    {
        if (($listeners = $this->listeners[$event][$priority] ?? null) === null) {
            $listeners = $this->listeners[$event][$priority] = new SplDoublyLinkedList();
            ksort($this->listeners[$event]);
        }

        $listeners->push($handler);
    }

    public function add(object $listener)
    {
        $rClass = new ReflectionClass($listener);

        foreach ($rClass->getMethods(ReflectionMethod::IS_PUBLIC) as $rMethod) {
            if (count($rParameters = $rMethod->getParameters()) !== 1) {
                continue;
            }
            $rParameter = $rParameters[0];
            if ($rParameter->getAttributes(Event::class) !== []) {
                $method = $rMethod->getName();

                $rType = $rParameter->getType();
                if ($rType instanceof ReflectionUnionType) {
                    foreach ($rType->getTypes() as $rType) {
                        $this->on($rType->getName(), [$listener, $method]);
                    }
                } else {
                    $type = $rType->getName();
                    $this->on($type === 'object' ? self::PEEKER : $type, [$listener, $method]);
                }
            }
        }
    }
}