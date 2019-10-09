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
        list($group, $type) = explode(':', $event, 2);
        if ($this->_listeners) {
            if (isset($this->_listeners[$group])) {
                foreach ($this->_listeners[$group] as $k => $v) {
                    /**@var \ManaPHP\Event\Listener $listener */
                    if (is_int($v)) {
                        $this->_listeners[$group][$k] = $listener = $this->_di->getShared($k);
                    } else {
                        $listener = $v;
                    }

                    $listener->process($type, $source, $data);
                }
            }
        }

        foreach ($this->_peekers['*'] ?? [] as $handler) {
            if ($handler instanceof Closure) {
                $handler($event, $source, $data);
            } else {
                $handler[0]->{$handler[1]}($event, $source, $data);
            }
        }

        foreach ($this->_peekers[$group] ?? [] as $handler) {
            if ($handler instanceof Closure) {
                $handler($event, $source, $data);
            } else {
                $handler[0]->{$handler[1]}($event, $source, $data);
            }
        }

        if (!isset($this->_events[$event])) {
            return;
        }

        foreach ($this->_events[$event] as $handler) {
            if ($handler instanceof Closure) {
                $handler($source, $data, $event);
            } else {
                $handler[0]->{$handler[1]}($source, $data, $event);
            }
        }
    }

    /**
     * @param callable $handler
     * @param string   $group
     *
     * @return static
     */
    public function peekEvent($handler, $group = '*')
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