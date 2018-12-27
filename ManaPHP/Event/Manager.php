<?php

namespace ManaPHP\Event;

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
     * @param string           $event
     * @param callable|\object $handler
     * @param bool             $appended
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
     *<code>
     *    $eventsManager->fire('db', $connection);
     *</code>
     *
     * @param string $event
     * @param mixed  $source
     * @param array  $data
     *
     * @return mixed|null
     */
    public function fireEvent($event, $source, $data = [])
    {
        if ($this->_listeners) {
            list($p1, $p2) = explode(':', $event, 2);
            if (isset($this->_listeners[$p1])) {
                foreach ($this->_listeners[$p1] as $k => $v) {
                    /**@var \ManaPHP\Event\Listener $listener */
                    if (is_int($v)) {
                        $this->_listeners[$p1][$k] = $listener = $this->_di->getShared($k);
                    } else {
                        $listener = $v;
                    }

                    if (($ret = $listener->process($p2, $source, $data)) !== null) {
                        return $ret;
                    }
                }
            }
        }

        foreach ($this->_peekers as $handler) {
            if ($handler instanceof \Closure) {
                $handler($event, $source, $data);
            } else {
                $handler[0]->{$handler[1]}($event, $source, $data);
            }
        }

        if (!isset($this->_events[$event])) {
            return null;
        }

        foreach ($this->_events[$event] as $handler) {
            if ($handler instanceof \Closure) {
                $ret = $handler($source, $data, $event);
            } else {
                $ret = $handler[0]->{$handler[1]}($source, $data, $event);
            }

            if ($ret !== null) {
                return $ret;
            }
        }

        return null;
    }

    /**
     * @param callable $handler
     *
     * @return static
     */
    public function peekEvent($handler)
    {
        $this->_peekers[] = $handler;

        return $this;
    }

    /**
     * @param string $listener
     * @param string $type
     *
     * @return static
     */
    public function addListener($listener, $type = null)
    {
        if (!$type) {
            $type = basename(substr($listener, strrpos($listener, '\\') + 1), 'Listener');
            $type = lcfirst(rtrim($type, '0123456789'));
        }

        $this->_listeners[$type][$listener] = 1;

        return $this;
    }
}