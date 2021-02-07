<?php

namespace ManaPHP\Event;

use ManaPHP\Component;
use SplDoublyLinkedList;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var SplDoublyLinkedList[][]
     */
    protected $events = [];

    /**
     * @var array
     */
    protected $peekers = [];

    /**
     * @var array
     */
    protected $listeners = [];

    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     * @param int      $priority
     *
     * @return void
     */
    public function attachEvent($event, $handler, $priority = 0)
    {
        if (($handlers = $this->events[$event][$priority] ?? null) === null) {
            $handlers = $this->events[$event][$priority] = new SplDoublyLinkedList();
            ksort($this->events[$event]);
        }

        $handlers->push($handler);
    }

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return void
     */
    public function detachEvent($event, $handler)
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

    /**
     * Fires an event in the events manager causing that active listeners be notified about it
     *
     * @param string $event
     * @param mixed  $data
     * @param mixed  $source
     *
     * @return \ManaPHP\Event\EventArgs
     */
    public function fireEvent($event, $data = null, $source = null)
    {
        $eventArgs = new EventArgs($event, $source, $data);

        list($group) = explode(':', $event, 2);

        if ($this->listeners && isset($this->listeners[$group])) {
            foreach ($this->listeners[$group] as $k => $v) {
                /**@var \ManaPHP\Event\Listener $listener */
                if (is_int($v)) {
                    $this->listeners[$group][$k] = $listener = $this->getShared($k);
                } else {
                    $listener = $v;
                }

                $listener->process($eventArgs);
            }
        }

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

    /**
     * @param string   $group
     * @param callable $handler
     *
     * @return static
     */
    public function peekEvent($group, $handler)
    {
        $this->peekers[$group][] = $handler;

        return $this;
    }

    /**
     * @param string $listener
     * @param string $group
     *
     * @return static
     */
    public function addListener($listener, $group = null)
    {
        if (!$group) {
            $group = basename(substr($listener, strrpos($listener, '\\') + 1), 'Listener');
            $group = lcfirst(rtrim($group, '0123456789'));
        }

        $this->listeners[$group][$listener] = 1;

        return $this;
    }

    /**
     * @return array
     */
    public function dump()
    {
        $dump = parent::dump();

        $dump['*events'] = array_keys($dump['events']);
        $dump['*peekers'] = array_keys($dump['peekers']);
        $dump['*listeners'] = array_keys($dump['listeners']);

        unset($dump['events'], $dump['peekers'], $dump['listeners']);

        return $dump;
    }
}