<?php
declare(strict_types=1);

namespace ManaPHP\Pooling\Pool\Event;

use ManaPHP\Pooling\PoolManagerInterface;

class PoolPopped
{
    public function __construct(
        public PoolManagerInterface $poolManager,
        public object $owner,
        public mixed $instance,
        public string $type,
    ) {

    }
}