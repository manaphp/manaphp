<?php
namespace ManaPHP\Redis;

use ManaPHP\Component;
use ManaPHP\Exception\DsnFormatException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Redis\Exception as RedisException;
use Redis;

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
    protected $_timeout = 0.0;

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
     * @var int
     */
    protected $_heartbeat = 60;

    /**
     * @var \Redis
     */
    protected $_redis;

    /**
     * @var float
     */
    protected $_last_heartbeat;

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

        $this->_host = $parts['host'] ?? '127.0.0.1';
        $this->_port = isset($parts['port']) ? (int)$parts['port'] : 6379;

        if (isset($parts['path'])) {
            $path = trim($parts['path'], '/');
            if ($path !== '' && !is_numeric($path)) {
                throw new DsnFormatException(['`:uri` is invalid, `:db` db is not integer', 'uri' => $uri, 'db' => $path]);
            }
            $this->_db = (int)$path;
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            if (isset($query['db'])) {
                $this->_db = (int)$query['db'];
            }

            if (isset($query['auth'])) {
                $this->_auth = $query['auth'];
            }

            if (isset($query['timeout'])) {
                $this->_timeout = (float)$query['timeout'];
            }

            if (isset($query['persistent'])) {
                $this->_persistent = !MANAPHP_COROUTINE_ENABLED && $query['persistent'] === '1';
            }

            if (isset($query['heartbeat'])) {
                $this->_heartbeat = $query['heartbeat'];
            }
        }
    }

    public function __clone()
    {
        $this->_redis = null;
        $this->_last_heartbeat = null;
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
            $this->fireEvent('redis:connect', ['uri' => $this->_uri]);

            $redis = $this->_di->get('Redis');

            if ($this->_persistent) {
                if (!@$redis->pconnect($this->_host, $this->_port, $this->_timeout, $this->_db)) {
                    throw new ConnectionException(['connect to `:uri` failed', 'uri' => $this->_uri]);
                }
            } elseif (!@$redis->connect($this->_host, $this->_port, $this->_timeout)) {
                throw new ConnectionException(['connect to `:uri` failed', 'uri' => $this->_uri]);
            }

            if ($this->_auth && !$redis->auth($this->_auth)) {
                throw new AuthException(['`:auth` auth is wrong.', 'auth' => $this->_auth]);
            }

            if ($this->_db !== 0 && !$redis->select($this->_db)) {
                throw new RuntimeException(['select `:db` db failed', 'db' => $this->_db]);
            }

            $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
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
            $this->_redis->ping();
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
            $this->_last_heartbeat = null;
        }
    }

    public function call($name, $arguments)
    {
        $redis = $this->getConnect();

        try {
            $r = @$redis->$name(...$arguments);
        } catch (\Exception  $exception) {
            $r = null;
            $failed = true;
            if (!$this->_ping()) {
                $this->close();
                $this->getConnect();

                try {
                    $r = @$redis->$name(...$arguments);
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
