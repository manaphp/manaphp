<?php

namespace ManaPHP\Event;

class EventArgs
{
    /**
     * @var string
     */
    public $event;

    /**
     * @var \ManaPHP\Component
     */
    public $source;

    /**
     * @var mixed
     */
    public $data;

    /**
     * @param string $event
     * @param object $source
     * @param mixed  $data
     */
    public function __construct($event, $source, $data)
    {
        $this->event = $event;
        $this->source = $source;
        $this->data = $data;
    }
}