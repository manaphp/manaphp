<?php

namespace ManaPHP\Event;

use ManaPHP\Event\Manager\Exception as ManagerException;
use ManaPHP\Utility\Text;

/**
 * ManaPHP\Event\Manager
 *
 * ManaPHP Event Manager, offers an easy way to intercept and manipulate, if needed,
 * the normal flow of operation. With the EventsManager the developer can create hooks or
 * plugins that will offer monitoring of data, manipulation, conditional execution and much more.
 *
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
    protected $_peekHandlers = [];

    /**
     * Attach a listener to the events manager
     *
     * @param string   $event
     * @param callable $handler
     *
     * @return void
     * @throws \ManaPHP\Event\Manager\Exception
     */
    public function attachEvent($event, $handler)
    {
        if (!is_object($handler) && !is_callable($handler)) {
            throw new ManagerException('Event handler must be callable or object'/**m0d76daa4bcd2ee5b6*/);
        }

        if (Text::contains($event, ':')) {
            $parts = explode(':', $event);

            $type = $parts[0];
            /** @noinspection MultiAssignmentUsageInspection */
            $name = $parts[1];
        } else {
            $type = $event;
            $name = '';
        }

        if (!isset($this->_events[$type])) {
            $this->_events[$type] = [];
        }

        $this->_events[$type][] = [
            'event' => $event,
            'name' => $name,
            'handler' => $handler,
        ];
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
     * @return boolean|null
     * @throws \ManaPHP\Event\Manager\Exception
     */
    public function fireEvent($event, $source, $data = [])
    {
        foreach ($this->_peekHandlers as $peekHandler) {
            $peekHandler($source, $data, $event);
        }

        if (!Text::contains($event, ':')) {
            throw new ManagerException('`:event` event must contains `:`'/**m01def78f0cd339c76*/, ['event' => $event]);
        }

        $parts = explode(':', $event, 2);
        $fire_type = $parts[0];
        /** @noinspection MultiAssignmentUsageInspection */
        $fire_name = $parts[1];

        if (!isset($this->_events[$fire_type])) {
            return null;
        }

        $callback_params = [$source, $data, new Event($fire_name)];

        $ret = null;
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_events[$fire_type] as $event_handler) {
            $name = $event_handler['name'];

            if ($name !== '' && $name !== $fire_name) {
                continue;
            }

            $handler = $event_handler['handler'];

            if (is_object($handler) && !$handler instanceof \Closure) {
                if (!method_exists($handler, $fire_name)) {
                    continue;
                } else {
                    $handler = [$handler, $fire_name];
                }
            }

            $ret = call_user_func_array($handler, $callback_params);
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
        $this->_peekHandlers[] = $handler;
    }
}