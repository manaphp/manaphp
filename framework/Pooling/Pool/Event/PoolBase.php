<?php
declare(strict_types=1);

namespace ManaPHP\Pooling\Pool\Event;

use ManaPHP\Pooling\PoolManagerInterface;
use Stringable;

class PoolBase implements Stringable
{
    public function __construct(
        public PoolManagerInterface $poolManager,
        public object $owner,
        public object $instance,
        public string $type,
    ) {

    }

    public function __toString(): string
    {
        return json_stringify(
            ['owner'    => $this->owner::class,
             'instance' => $this->instance::class . '#' . spl_object_id($this->instance),
             'type'     => $this->type
            ]
        );
    }
}