<?php

namespace ManaPHP\Event;

use ManaPHP\Event\Manager\Exception as ManagerException;

/**
 * Class ManaPHP\Event\Manager
 *
 * @package eventsManager
 */
class Manager implements ManagerInterface
{
    /**
     * @var array
     */
    protected $_events = [];

    /**
     * @var array
     */
    protected $_peeks = [];

    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     *
     * @return void
     */
    public function attachEvent($event, $handler)
    {
        $this->_events[$event][] = $handler;
    }

    /**
     * Fires an event in the events manager causing that active listeners be notified about it
     *
     *<code>
     *    $eventsManager->fire('db', $connection);
     *</code>
     *
     * @param string                         $event
     * @param \ManaPHP\Component|\ManaPHP\Di $source
     * @param array                          $data
     *
     * @return bool|null
     * @throws \ManaPHP\Event\Manager\Exception
     */
    public function fireEvent($event, $source, $data = [])
    {
        foreach ($this->_peeks as $handler) {
            if ($handler instanceof \Closure) {
                $handler($source, $data, $event);
            } else {
                $handler[0]->{$handler[1]}($source, $data, $event);
            }
        }

        if (!isset($this->_events[$event])) {
            return null;
        }

        $ret = null;
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_events[$event] as $i => $handler) {
            if ($handler instanceof \Closure) {
                $ret = $handler($source, $data, $event);
            } else {
                $ret = $handler[0]->{$handler[1]}($source, $data, $event);
            }

            if ($ret === false && $i !== count($this->_events[$event]) - 1) {
                throw new ManagerException('`:event` event is canceled  too early'/**m034048b6f1b217155*/, ['event' => $event]);
            }
        }

        return $ret;
    }

    /**
     * @param callable $handler
     *
     * @return void
     */
    public function peekEvents($handler)
    {
        $this->_peeks[] = $handler;
    }
}