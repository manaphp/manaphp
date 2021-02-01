<?php

namespace ManaPHP\Event;

use ManaPHP\Component;
use SplDoublyLinkedList;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var SplDoublyLinkedList[][]
     */
    protected $_events = [];

    /**
     * @var array
     */
    protected $_peekers = [];

    /**
     * @var array
     */
    protected $_listeners = [];

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
        if (($handlers = $this->_events[$event][$priority] ?? null) === null) {
            $handlers = $this->_events[$event][$priority] = new SplDoublyLinkedList();
            ksort($this->_events[$event]);
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
            foreach ($this->_events[$event] ?? [] as $handlers) {
                foreach ($handlers as $kk => $vv) {
                    if ($vv === $handler) {
                        unset($handlers[$kk]);
                    }
                }
            }
        } else {
            foreach ($this->_peekers[$event] ?? [] as $k => $v) {
                if ($v === $handler) {
                    unset($this->_peekers[$event][$k]);
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

        if ($this->_listeners && isset($this->_listeners[$group])) {
            foreach ($this->_listeners[$group] as $k => $v) {
                /**@var \ManaPHP\Event\Listener $listener */
                if (is_int($v)) {
                    $this->_listeners[$group][$k] = $listener = $this->getShared($k);
                } else {
                    $listener = $v;
                }

                $listener->process($eventArgs);
            }
        }

        foreach ($this->_peekers['*'] ?? [] as $handler) {
            $handler($eventArgs);
        }

        foreach ($this->_peekers[$group] ?? [] as $handler) {
            $handler($eventArgs);
        }

        foreach ($this->_events[$event] ?? [] as $handlers) {
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
        $this->_peekers[$group][] = $handler;

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

        $this->_listeners[$group][$listener] = 1;

        return $this;
    }

    /**
     * @return array
     */
    public function dump()
    {
        $dump = parent::dump();

        $dump['*_events'] = array_keys($dump['_events']);
        $dump['*_peekers'] = array_keys($dump['_peekers']);
        $dump['*_listeners'] = array_keys($dump['_listeners']);

        unset($dump['_events'], $dump['_peekers'], $dump['_listeners']);

        return $dump;
    }
}