<?php
namespace ManaPHP;

use ManaPHP\Exception\InvalidValueException;
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
     * @var bool
     */
    protected $_persistent = false;

    /**
     * Redis constructor.
     *
     * @param string $uri
     */
    public function __construct($uri = 'redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0')
    {
        $parts = parse_url($uri);

        if ($parts['scheme'] !== 'redis') {
            throw new InvalidValueException(['`:url` is invalid, `:scheme` scheme is not recognized', 'url' => $uri, 'scheme' => $parts['scheme']]);
        }

        $this->_host = isset($parts['host']) ? $parts['host'] : '127.0.0.1';
        $this->_port = isset($parts['port']) ? (int)$parts['port'] : 6379;

        if (isset($parts['path'])) {
            $path = trim($parts['path'], '/');
            if ($path !== '') {
                if (!is_numeric($path)) {
                    throw new InvalidValueException(['`:url` url is invalid, `:db` db is not integer', 'url' => $uri, 'db' => $path]);
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
        $this->_persistent = isset($parts2['persistent']) && $parts2['persistent'] === '1';

        parent::__construct();

        $this->_connect();
    }

    /**
     * @throws \ManaPHP\Redis\Exception
     */
    protected function _connect()
    {
        if ($this->_persistent) {
            $this->pconnect($this->_host, $this->_port, $this->_timeout, $this->_db);
        } else {
            $this->connect($this->_host, $this->_port, $this->_timeout, null, $this->_retry_interval);
        }

        if ($this->_auth !== '' && !$this->auth($this->_auth)) {
            throw new RedisException(['`:auth` auth is wrong.', 'auth' => $this->_auth]);
        }

        if ($this->_db !== 0 && !$this->select($this->_db)) {
            throw new RedisException(['select `:db` db failed', 'db' => $this->_db]);
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
        return $key === null ? get_object_vars($this) : parent::dump($key);
    }
}