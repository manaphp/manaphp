<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Component;
use ManaPHP\Data\Redis\Connection;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Pool\Transient;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Redis extends Component implements RedisInterface, RedisDbInterface, RedisCacheInterface, RedisBrokerInterface
{
    protected string $uri;
    protected float $timeout = 1.0;
    protected int $pool_size = 4;

    public function __construct(string|array $options = 'redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0'
    ) {
        if (is_string($options)) {
            $this->uri = $options;

            if (preg_match('#timeout=([\d.]+)#', $options, $matches) === 1) {
                $this->timeout = (float)$matches[1];
            }

            if (preg_match('#pool_size=([\d/]+)#', $options, $matches)) {
                $this->pool_size = (int)$matches[1];
            }
        } else {
            $this->uri = $options['uri'];

            if (isset($options['timeout'])) {
                $this->timeout = (float)$options['timeout'];
            }

            if (isset($options['pool_size'])) {
                $this->pool_size = (int)$options['pool_size'];
            }
        }

        $sample = $this->container->make('ManaPHP\Data\Redis\Connection', [$this->uri]);
        $this->poolManager->add($this, $sample, $this->pool_size);
    }

    public function getTransientWrapper(string $type = 'default'): Transient
    {
        return $this->poolManager->transient($this, $this->timeout, $type);
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function call(string $method, array $arguments, ?Connection $connection = null): mixed
    {
        if (str_contains(',watch,unwatch,multi,pipeline,', ",$method,")) {
            if ($connection === null) {
                throw new MisuseException(["`%s` method can only be called in a transient wrapper", $method]);
            }
        }

        if ($connection) {
            return $connection->call($method, $arguments);
        } else {
            $connection = $this->poolManager->pop($this, $this->timeout);
            try {
                return $connection->call($method, $arguments);
            } finally {
                $this->poolManager->push($this, $connection);
            }
        }
    }

    public function transientCall(object $instance, string $method, array $arguments): mixed
    {
        $this->fireEvent('redis:calling', compact('method', 'arguments'));

        $return = $this->call($method, $arguments, $instance);

        $this->fireEvent('redis:called', compact('method', 'arguments', 'return'));

        return $return;
    }

    public function __call(string $method, array $arguments): mixed
    {
        $this->fireEvent('redis:calling', compact('method', 'arguments'));

        $return = $this->call($method, $arguments);

        $this->fireEvent('redis:called', compact('method', 'arguments', 'return'));

        return $return;
    }
}
