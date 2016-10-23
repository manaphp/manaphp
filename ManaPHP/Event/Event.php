<?php

namespace ManaPHP\Event;

/**
 * Class ManaPHP\Event\Event
 *
 * @package ManaPHP\Event
 */
class Event
{
    /**
     * Event type
     *
     * @var string
     */
    protected $_type;

    /**
     * \ManaPHP\Event\Event constructor
     *
     * @param string $type
     */
    public function __construct($type)
    {
        $this->_type = $type;
    }

    /**
     * Returns the event's type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }
}