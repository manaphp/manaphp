<?php
declare(strict_types=1);

namespace ManaPHP\Event;

use SplDoublyLinkedList;

class Manager implements ManagerInterface
{
    /**
     * @var SplDoublyLinkedList[][]
     */
    protected array $events = [];
    protected array $peekers = [];

    public function attachEvent(string $event, callable $handler, int $priority = 0): void
    {
        if (($handlers = $this->events[$event][$priority] ?? null) === null) {
            $handlers = $this->events[$event][$priority] = new SplDoublyLinkedList();
            ksort($this->events[$event]);
        }

        $handlers->push($handler);
    }

    public function detachEvent(string $event, callable $handler): void
    {
        if (str_contains($event, ':')) {
            foreach ($this->events[$event] ?? [] as $handlers) {
                foreach ($handlers as $kk => $vv) {
                    if ($vv === $handler) {
                        unset($handlers[$kk]);
                    }
                }
            }
        } else {
            foreach ($this->peekers[$event] ?? [] as $k => $v) {
                if ($v === $handler) {
                    unset($this->peekers[$event][$k]);
                    break;
                }
            }
        }
    }

    public function fireEvent(string $event, mixed $data = null, ?object $source = null): EventArgs
    {
        $eventArgs = new EventArgs($event, $source, $data);

        list($group) = explode(':', $event, 2);

        foreach ($this->peekers['*'] ?? [] as $handler) {
            $handler($eventArgs);
        }

        foreach ($this->peekers[$group] ?? [] as $handler) {
            $handler($eventArgs);
        }

        foreach ($this->events[$event] ?? [] as $handlers) {
            foreach ($handlers as $handler) {
                $handler($eventArgs);
            }
        }

        return $eventArgs;
    }

    public function peekEvent(string $group, callable $handler): void
    {
        $this->peekers[$group][] = $handler;
    }

    public function dump(): array
    {
        $data = [];

        $data['*events'] = array_keys($this->events);
        $data['*peekers'] = array_keys($this->peekers);

        return $data;
    }
}