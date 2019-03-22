<?php
namespace ManaPHP\Redis;

use ManaPHP\Component;
use ManaPHP\Exception\DsnFormatException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Redis\Exception as RedisException;

class Connection extends Component
{
    /**
     * @var string
     */
    protected $_uri;

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
     * @var int
     */
    protected $_retry_seconds = 60;

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
     * @var float
     */
    protected $_ping_interval = 10.0;

    /**
     * @var \Redis
     */
    protected $_redis;

    /**
     * @var float
     */
    protected $_last_io_time;

    /**
     * Connection constructor.
     *
     * @param string|\ManaPHP\Redis\Connection $uri
     *
     * @throws \ManaPHP\Exception\DsnFormatException
     */
    public function __construct($uri)
    {
        $this->_uri = $uri;

        $parts = parse_url($uri);

        if ($parts['scheme'] !== 'redis') {
            throw new DsnFormatException(['`:uri` is invalid, `:scheme` scheme is not recognized', 'uri' => $uri, 'scheme' => $parts['scheme']]);
        }

        $this->_host = isset($parts['host']) ? $parts['host'] : '127.0.0.1';
        $this->_port = isset($parts['port']) ? (int)$parts['port'] : 6379;

        if (isset($parts['path'])) {
            $path = trim($parts['path'], '/');
            if ($path !== '' && !is_numeric($path)) {
                throw new DsnFormatException(['`:uri` is invalid, `:db` db is not integer', 'uri' => $uri, 'db' => $path]);
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
        if (isset($parts2['ping_interval'])) {
            $this->_ping_interval = $parts2['ping_interval'];
        }
    }

    public function __clone()
    {
        $this->_redis = null;
        $this->_last_io_time = null;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * @return \Redis
     */
    public function getConnect()
    {
        if ($this->_redis === null) {
            $redis = new \Redis();

            if ($this->_persistent) {
                if (!@$redis->pconnect($this->_host, $this->_port, $this->_timeout, $this->_db)) {
                    throw new ConnectionException(['connect to `:uri` failed', 'uri' => $this->_uri]);
                }
            } elseif (!@$redis->connect($this->_host, $this->_port, $this->_timeout, null, $this->_retry_interval)) {
                throw new ConnectionException(['connect to `:uri` failed', 'uri' => $this->_uri]);
            }

            if ($this->_auth !== '' && !$redis->auth($this->_auth)) {
                throw new AuthException(['`:auth` auth is wrong.', 'auth' => $this->_auth]);
            }

            if ($this->_db !== 0 && !$redis->select($this->_db)) {
                throw new RuntimeException(['select `:db` db failed', 'db' => $this->_db]);
            }

            $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
            $this->_redis = $redis;
        }

        return $this->_redis;
    }

    /**
     * @return bool
     */
    protected function _ping()
    {
        try {
            $this->redis->ping();
            return true;
        } catch (\Exception  $exception) {
            return false;
        }
    }

    public function close()
    {
        if ($this->_redis) {
            $this->_redis->close();
            $this->_redis = null;
            $this->_last_io_time = null;
        }
    }

    public function call($name, $arguments)
    {
        $redis = $this->getConnect();
        try {
            switch (count($arguments)) {
                case 0:
                    $r = @$redis->$name();
                    break;
                case 1:
                    $r = @$redis->$name($arguments[0]);
                    break;
                case 2:
                    $r = @$redis->$name($arguments[0], $arguments[1]);
                    break;
                case 3:
                    $r = @$redis->$name($arguments[0], $arguments[1], $arguments[2]);
                    break;
                case 4:
                    $r = @$redis->$name($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
                    break;
                case 5:
                    $r = @$redis->$name($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
                    break;
                default:
                    $r = @call_user_func_array([$redis, $name], $arguments);
                    break;
            }
        } catch (\Exception  $exception) {
            $r = null;
            $failed = true;
            if (!$this->_ping()) {
                $this->close();
                $this->getConnect();

                try {
                    $r = @call_user_func_array([$redis, $name], $arguments);
                    $failed = false;
                } /** @noinspection PhpRedundantCatchClauseInspection */
                catch (\RedisException $exception) {
                }
            }

            if ($failed) {
                throw new RedisException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        return $r;
    }
}