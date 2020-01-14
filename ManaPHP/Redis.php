<?php
namespace ManaPHP;

/**
 * Class Redis
 * @package ManaPHP
 */
class Redis extends Component
{
    const SERVE_AS_CACHE = 'cache';
    const SERVE_AS_DB = 'db';
    const SERVE_AS_BROKER = 'broker';
    const SERVE_AS_ANY = '';

    /**
     * @var string
     */
    protected $_uri;

    /**
     * @var float
     */
    protected $_timeout = 1.0;

    /**
     * @var string
     */
    protected $_serve_as = self::SERVE_AS_ANY;

    /**
     * Redis constructor.
     *
     * @param string $uri
     */
    public function __construct($uri = 'redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0')
    {
        if ($uri === null) {
            $uri = Di::getDefault()->getShared('redis')->getUri();
        }
        $this->_uri = $uri;

        if (preg_match('#timeout=([\d.]+)#', $uri, $matches) === 1) {
            $this->_timeout = (float)$matches[1];
        }

        $pool_size = preg_match('#pool_size=(\d+)#', $uri, $matches) ? $matches[1] : 4;
        $this->poolManager->add($this, ['class' => 'ManaPHP\Redis\Connection', $uri], $pool_size);
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function call($name, ...$arguments)
    {
        $connection = $this->poolManager->pop($this, $this->_timeout);

        try {
            $r = $connection->call($name, $arguments);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        return $r;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function __call($name, $arguments)
    {
        $this->fireEvent('redis:calling', ['name' => $name, 'arguments' => $arguments]);

        $connection = $this->poolManager->pop($this, $this->_timeout);

        try {
            $r = $connection->call($name, $arguments);
        } finally {
            $this->poolManager->push($this, $connection);
        }

        $this->fireEvent('redis:called', ['name' => $name, 'arguments' => $arguments, 'return' => $r]);

        return $r;
    }

    /**
     * @return string
     */
    public function getServeAs()
    {
        return $this->_serve_as;
    }
}
