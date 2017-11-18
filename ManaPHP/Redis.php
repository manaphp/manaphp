<?php
namespace ManaPHP;

use ManaPHP\Redis\Exception as RedisException;

class Redis extends \Redis
{
    /**
     * @var string
     */
    protected $_host;

    /**
     * @var int
     */
    protected $_port;

    /**
     * @var float
     */
    protected $_timeout;

    /**
     * @var int
     */
    protected $_retry_interval;

    /**
     * @var string
     */
    protected $_auth;

    /**
     * @var int
     */
    protected $_db = 0;

    /**
     * Redis constructor.
     *
     * @param string $uri
     *
     * @throws \ManaPHP\Redis\Exception
     */
    public function __construct($uri = 'redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=')
    {
        $parts = parse_url($uri);

        if ($parts['scheme'] !== 'redis') {
            throw new RedisException('`:url` is invalid, `:scheme` scheme is not recognized', ['url' => $uri, 'scheme' => $parts['scheme']]);
        }

        $this->_host = isset($parts['host']) ? $parts['host'] : '127.0.0.1';
        $this->_port = isset($parts['port']) ? (int)$parts['port'] : 6379;

        if (isset($parts['path'])) {
            $path = trim($parts['path'], '/');
            if ($path !== '') {
                if (!is_numeric($path)) {
                    throw new RedisException('`:url` url is invalid, `:db` db is not integer', ['url' => $uri, 'db' => $path]);
                }
            }
            $this->_db = (int)$path;
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $parts2);
        } else {
            $parts2 = [];
        }

        $this->_timeout = isset($parts2['timeout']) ? (float)$parts2['timeout'] : 0.0;
        $this->_retry_interval = isset($parts2['retry_interval']) ? (int)$parts2['retry_interval'] : 0;
        $this->_auth = isset($parts2['auth']) ? $parts2['auth'] : '';

        parent::__construct();

        $this->_connect();
    }

    /**
     * @throws \ManaPHP\Redis\Exception
     */
    protected function _connect()
    {
        $this->connect($this->_host, $this->_port, $this->_timeout, null, $this->_retry_interval);

        if ($this->_auth !== '' && !$this->auth($this->_auth)) {
            throw new RedisException('`:auth` auth is wrong.', ['auth' => $this->_auth]);
        }

        if ($this->_db !== 0 && !$this->select($this->_db)) {
            throw new RedisException('select `:db` db failed', ['db' => $this->_db]);
        }
    }

    /**
     * @return static
     * @throws \ManaPHP\Redis\Exception
     */
    public function reconnect()
    {
        $this->close();
        $this->_connect();

        return $this;
    }

    /**
     * @param string $key
     *
     * @return array|string
     */
    public function dump($key = null)
    {
        if ($key === null) {
            return get_object_vars($this);
        } else {
            return parent::dump($key);
        }
    }
}