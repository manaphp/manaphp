<?php

namespace ManaPHP\Amqp;

use JsonSerializable;

class Queue implements JsonSerializable
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $features;

    /**
     * @param string $name
     * @param array  $features
     */
    public function __construct($name, $features = [])
    {
        $this->name = $name;

        $this->features = $features + [
                'passive'     => false,
                'durable'     => true,
                'exclusive'   => false,
                'auto_delete' => false,
                'nowait'      => false,
                'arguments'   => [],
            ];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}