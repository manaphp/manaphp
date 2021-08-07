<?php

namespace ManaPHP\Data;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Exception\NotSupportedException;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Redis extends Component implements RedisInterface
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var float
     */
    protected $timeout = 1.0;

    /**
     * @var string
     */
    protected $pool_size = '4';

    /**
     * @param string $uri
     */
    public function __construct($uri = 'redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0')
    {
        $this->uri = $uri;

        if (preg_match('#timeout=([\d.]+)#', $uri, $matches) === 1) {
            $this->timeout = (float)$matches[1];
        }

        if (preg_match('#pool_size=([\d/]+)#', $uri, $matches)) {
            $this->pool_size = $matches[1];
        }

        $this->poolManager->add($this, ['class' => 'ManaPHP\Data\Redis\Connection', $uri], $this->pool_size);
    }

    /**
     * @return static
     */
    public function getTransientWrapper()
    {
        return $this->poolManager->transient($this, $this->timeout);
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string                         $method
     * @param array                          $arguments
     * @param \ManaPHP\Data\Redis\Connection $connection
     *
     * @return mixed
     */
    public function call($method, $arguments, $connection = null)
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

    public function transientCall($instance, $method, $arguments)
    {
        $this->fireEvent('redis:calling', compact('method', 'arguments'));

        $return = $this->self->call($method, $arguments, $instance);

        $this->fireEvent('redis:called', compact('method', 'arguments', 'return'));

        return $return;
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function __call($method, $arguments)
    {
        $this->fireEvent('redis:calling', compact('method', 'arguments'));

        $return = $this->self->call($method, $arguments);

        $this->fireEvent('redis:called', compact('method', 'arguments', 'return'));

        return $return;
    }
}
