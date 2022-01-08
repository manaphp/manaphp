<?php
declare(strict_types=1);

namespace ManaPHP\Amqp;

use JsonSerializable;

class Queue implements JsonSerializable
{
    public string $name;
    public array $features;

    /**
     * @param string $name
     * @param array  $features
     */
    public function __construct(string $name, array $features = [])
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

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}