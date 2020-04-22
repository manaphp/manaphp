<?php

namespace ManaPHP\Event;

use Closure;

/**
 * Class ManaPHP\Event\Manager
 *
 * @package eventsManager
 */
class Manager implements ManagerInterface
{
    /**
     * @var \ManaPHP\DiInterface
     */
    protected $_di;

    /**
     * @var array[]
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
     * @return \ManaPHP\DiInterface
     */
    public function getDi()
    {
        return $this->_di;
    }

    /**
     * @param \ManaPHP\DiInterface $di
     *
     * @return void
     */
    public function setDi($di)
    {
        $this->_di = $di;
    }

    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     * @param bool     $appended
     *
     * @return void
     */
    public function attachEvent($event, $handler, $appended = true)
    {
        if ($appended) {
            $this->_events[$event][] = $handler;
        } else {
            array_unshift($this->_events[$event], $handler);
        }
    }

    /**
     * @param string   $event
     * @param callable $handler
     *
     * @return void
     */
    public function detachEvent($event, $handler)
    {
        if (strpos($event, ':') === false) {
            foreach ($this->_peekers[$event] ?? [] as $k => $v) {
                if ($v === $handler) {
                    unset($this->_peekers[$event][$k]);
                    break;
                }
            }
        } else {
            foreach ($this->_events[$event] ?? [] as $k => $v) {
                if ($v === $handler) {
                    unset($this->_events[$event][$k]);
                    break;
                }
            }
        }
    }

    /**
     * Fires an event in the events manager causing that active listeners be notified about it
     *
     * @param string $event
     * @param mixed  $source
     * @param mixed  $data
     *
     * @return void
     */
    public function fireEvent($event, $source, $data = [])
    {
        $eventArgs = new EventArgs($event, $source, $data);

        list($group) = explode(':', $event, 2);

        if ($this->_listeners && isset($this->_listeners[$group])) {
            foreach ($this->_listeners[$group] as $k => $v) {
                /**@var \ManaPHP\Event\Listener $listener */
                if (is_int($v)) {
                    $this->_listeners[$group][$k] = $listener = $this->_di->getShared($k);
                } else {
                    $listener = $v;
                }

                $listener->process($eventArgs);
            }
        }

        foreach ($this->_peekers['*'] ?? [] as $handler) {
            $handler instanceof Closure ? $handler($eventArgs) : $handler[0]->{$handler[1]}($eventArgs);
        }

        foreach ($this->_peekers[$group] ?? [] as $handler) {
            $handler instanceof Closure ? $handler($eventArgs) : $handler[0]->{$handler[1]}($eventArgs);
        }

        foreach ($this->_events[$event] ?? [] as $handler) {
            $handler instanceof Closure ? $handler($eventArgs) : $handler[0]->{$handler[1]}($eventArgs);
        }
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

    public function __debugInfo()
    {
        $data = get_object_vars($this);

        unset($data['_di']);

        return $data;
    }
}