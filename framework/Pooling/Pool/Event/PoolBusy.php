<?php
declare(strict_types=1);

namespace ManaPHP\Pooling\Pool\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Pooling\PoolsInterface;
use Stringable;

#[Verbosity(Verbosity::LOW)]
class PoolBusy implements Stringable
{
    public function __construct(
        public PoolsInterface $pools,
        public object $owner,
        public string $type,
        public int $capacity,
        public float $timout,
    ) {

    }

    public function __toString(): string
    {
        return json_stringify(
            ['owner'    => $this->owner::class,
             'type'     => $this->type,
             'capacity' => $this->capacity,
             'timout'   => $this->timout,
            ]
        );
    }
}