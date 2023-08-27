<?php
declare(strict_types=1);

namespace ManaPHP\Pooling\Pool\Event;

use ManaPHP\Pooling\PoolManagerInterface;

class PoolPopping
{
    public function __construct(
        public PoolManagerInterface $poolManager,
        public object $owner,
        public string $type,
    ) {

    }

    public function __toString()
    {
        return json_stringify(
            ['owner' => $this->owner::class,
             'type'  => $this->type
            ]
        );

    }
}