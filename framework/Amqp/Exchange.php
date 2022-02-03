<?php
declare(strict_types=1);

namespace ManaPHP\Amqp;

use JsonSerializable;

class Exchange implements JsonSerializable
{
    public string $name;
    public string $type;
    public array $features;

    public function __construct(string $name, string $type = 'direct', array $features = [])
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

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}