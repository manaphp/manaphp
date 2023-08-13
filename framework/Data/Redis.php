<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Component;
use ManaPHP\Data\Redis\Connection;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Event\EventTrait;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Pool\ManagerInterface as PoolManager;

class Redis extends Component implements RedisInterface, RedisDbInterface, RedisCacheInterface, RedisBrokerInterface
{
    use EventTrait;

    #[Inject] protected PoolManager $poolManager;
    #[Inject] protected MakerInterface $maker;

    protected string $uri;
    protected float $timeout = 1.0;
    protected int $pool_size = 4;

    protected Redis $owner;
    protected ?Connection $connection = null;

    /**
     * @param string $uri redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0
     */
    public function __construct(string $uri)
    {
        $this->owner = $this;

        $this->uri = $uri;

        if (preg_match('#timeout=([\d.]+)#', $uri, $matches) === 1) {
            $this->timeout = (float)$matches[1];
        }

        if (preg_match('#pool_size=([\d/]+)#', $uri, $matches)) {
            $this->pool_size = (int)$matches[1];
        }

        $sample = $this->maker->make('ManaPHP\Data\Redis\Connection', [$this->uri]);
        $this->poolManager->add($this, $sample, $this->pool_size);
    }

    public function __clone()
    {
        if ($this->connection !== null) {
            throw new MisuseException('this is a cloned already.');
        }

        $this->connection = $this->poolManager->pop($this->owner, $this->timeout);
    }

    public function __destruct()
    {
        if ($this->connection !== null) {
            $this->poolManager->push($this->owner, $this->connection);
        }
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function call(string $method, array $arguments): mixed
    {
        if (str_contains(',watch,multi,pipeline,', ",$method,")) {
            $that = $this->connection === null ? clone $this : $this;
            $r = $that->connection->call($method, $arguments);
            return is_object($r) ? $that : $r;
        } elseif ($this->connection !== null) {
            $r = $this->connection->call($method, $arguments);
        } else {
            $connection = $this->poolManager->pop($this, $this->timeout);
            try {
                $r = $connection->call($method, $arguments);
            } finally {
                $this->poolManager->push($this, $connection);
            }
        }

        return is_object($r) ? $this : $r;
    }

    public function __call(string $method, array $arguments): mixed
    {
        $this->fireEvent('redis:calling', compact('method', 'arguments'));

        $return = $this->call($method, $arguments);

        $this->fireEvent('redis:called', compact('method', 'arguments', 'return'));

        return $return;
    }
}
