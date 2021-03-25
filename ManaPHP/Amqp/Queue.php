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
     * @var Bind[]
     */
    public $binds = [];

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
     * @param string|Exchange $exchange
     * @param string          $binding_key
     * @param array           $arguments
     */
    public function bind($exchange, $binding_key, $arguments = [])
    {
        $this->binds[] = new Bind($this->name, $exchange, $binding_key, $arguments);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}