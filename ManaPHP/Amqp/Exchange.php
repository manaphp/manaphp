<?php

namespace ManaPHP\Amqp;

use JsonSerializable;

class Exchange implements JsonSerializable
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $features;

    /**
     * @param string $name
     * @param string $type
     * @param array  $features
     */
    public function __construct($name, $type = 'direct', $features = [])
    {
        $this->name = $name;
        $this->type = $type;

        $this->features = $features + [
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => false,
                'internal'    => false,
                'nowait'      => false,
                'arguments'   => []
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