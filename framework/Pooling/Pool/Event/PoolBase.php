<?php
declare(strict_types=1);

namespace ManaPHP\Pooling\Pool\Event;

use ManaPHP\Pooling\PoolManagerInterface;

class PoolBase
{
    public function __construct(
        public PoolManagerInterface $poolManager,
        public object $owner,
        public object $instance,
        public string $type,
    ) {

    }
}