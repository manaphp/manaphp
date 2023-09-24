<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Pooling\PoolManagerInterface;

class Redis implements RedisInterface
{
    #[Inject] protected PoolManagerInterface $poolManager;

    #[Value] protected string $uri; #redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0
    #[Value] protected int $pool_timeout = 1;
    #[Value] protected int $pool_size = 4;

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct()
    {
        if (preg_match('#pool_timeout=(\d+)#', $this->uri, $matches) === 1) {
            $this->pool_timeout = (int)$matches[1];
        }

        if (preg_match('#pool_size=(\d+)#', $this->uri, $matches) === 1) {
            $this->pool_size = (int)$matches[1];
        }

        $this->poolManager->add($this, [Connection::class, ['uri' => $this->uri]], $this->pool_size);
    }

    public function __call(string $method, array $arguments): mixed
    {
        if (preg_match('#^(watch|multi|pipeline|select|scan|[shz]Scan)$#', $method) === 1) {
            throw new CallInPoolException($method);
        }

        /** @var Connection $connection */
        $connection = $this->poolManager->pop($this, $this->pool_timeout);

        try {
            $return = $connection->__call($method, $arguments);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        if (is_object($return)) {
            throw new CallInPoolException($method);
        }

        return $return;
    }

    public function getProxy(): RedisInterface
    {
        $connection = $this->poolManager->pop($this, $this->pool_timeout);

        return new Proxy($this->poolManager, $this, $connection);
    }
}
