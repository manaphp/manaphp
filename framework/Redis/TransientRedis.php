<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Pooling\PoolsInterface;

class TransientRedis extends Redis
{
    /**
     * @noinspection PhpMissingParentConstructorInspection
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct(
        PoolsInterface $pools,
        protected object $owner,
        Connection $connection
    ) {
        $this->pools = $pools;
        $this->connection = $connection;
    }

    public function __destruct()
    {
        $this->pools->push($this->owner, $this->connection);
    }

    public function getTransientCopy(): static
    {
        return clone $this;
    }
}