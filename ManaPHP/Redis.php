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
     * @var array
     */
    protected $_types;

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
        $this->_uri = $uri ?: Di::getDefault()->getShared('redis')->getUri();

        if (preg_match('#timeout=([\d.]+)#', $uri, $matches) === 1) {
            $this->_timeout = (float)$matches[1];
        }

        if (strpos($uri, ',') !== false) {
            $urls = explode(',', $uri);
            if ($urls[0]) {
                $url = $urls[0];
                $pool_size = preg_match('#pool_size=(\d+)#', $url, $matches) ? $matches[1] : 4;
                $this->poolManager->add($this, ['class' => 'ManaPHP\Redis\Connection', $url], $pool_size);
            }
            array_shift($urls);

            $this->_types = [];
            foreach ($urls as $url) {
                $this->_types[] = $type = preg_match('#type=([^=?]+)#', $url, $matches) ? rtrim($matches[1], ':') . ':' : 'cache:';
                $pool_size = preg_match('#pool_size=(\d+)#', $url, $matches) ? $matches[1] : 4;
                $this->poolManager->add($this, ['class' => 'ManaPHP\Redis\Connection', $url], $pool_size, $type);
            }
        } else {
            $pool_size = preg_match('#pool_size=(\d+)#', $uri, $matches) ? $matches[1] : 4;

            $this->poolManager->add($this, ['class' => 'ManaPHP\Redis\Connection', $uri], $pool_size);
        }
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
     * @return string
     */
    protected function _getType(/** @noinspection PhpUnusedParameterInspection */ $name, $arguments)
    {
        if ($this->_types && isset($arguments[0]) && is_string($arguments[0])) {
            $key = $arguments[0];
            foreach ($this->_types as $type) {
                if (strncmp($key, $type, strlen($type)) === 0) {
                    return $type;
                }
            }
        }

        return 'default';
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function call($name, ...$arguments)
    {
        $type = $this->_types ? $this->_getType($name, $arguments) : 'default';

        $connection = $this->poolManager->pop($this, $this->_timeout, $type);

        try {
            $r = $connection->call($name, $arguments);
        } finally {
            $this->poolManager->push($this, $connection, $type);
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

        $type = $this->_types ? $this->_getType($name, $arguments) : 'default';

        $connection = $this->poolManager->pop($this, $this->_timeout, $type);

        try {
            $r = $connection->call($name, $arguments);
        } finally {
            $this->poolManager->push($this, $connection, $type);
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
