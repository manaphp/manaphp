<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\Eventing\Attribute\Event;
use ReflectionClass;
use ReflectionMethod;
use SplDoublyLinkedList;

class EventManager implements EventManagerInterface
{
    /**
     * @var SplDoublyLinkedList[][]
     */
    protected array $events = [];

    public function subscribe(string $event, callable $handler, int $priority = 0): void
    {
        if (($handlers = $this->events[$event][$priority] ?? null) === null) {
            $handlers = $this->events[$event][$priority] = new SplDoublyLinkedList();
            ksort($this->events[$event]);
        }

        $handlers->push($handler);
    }

    public function addListener(object $listener)
    {
        $rClass = new ReflectionClass($listener);

        foreach ($rClass->getMethods(ReflectionMethod::IS_PUBLIC) as $rMethod) {
            if (count($rParameters = $rMethod->getParameters()) !== 1) {
                continue;
            }
            $rParameter = $rParameters[0];
            if ($rParameter->getAttributes(Event::class) !== []) {
                $rType = $rParameter->getType();
                $type = $rType->getName();
                $this->subscribe($type === 'object' ? '*' : $type, [$listener, $rMethod->getName()]);
            }
        }
    }

    public function dispatch(object $event)
    {
        foreach ($this->events['*'] ?? [] as $handlers) {
            foreach ($handlers as $handler) {
                $handler($event);
            }
        }

        foreach ($this->events[$event::class] ?? [] as $handlers) {
            foreach ($handlers as $handler) {
                $handler($event);
            }
        }
    }
}