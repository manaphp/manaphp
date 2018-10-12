<?php
namespace ManaPHP;

use ManaPHP\Exception\DsnFormatException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Redis\AuthException;
use ManaPHP\Redis\ConnectionException;

class Redis extends Component
{
    /**
     * @var string
     */
    protected $_url;

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
     * @var \Redis
     */
    protected $_redis;

    /**
     * @var float
     */
    protected $_lastIoTime;

    /**
     * Redis constructor.
     *
     * @param string $url
     */
    public function __construct($url = 'redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0')
    {
        $this->_url = $url;

        $parts = parse_url($url);

        if ($parts['scheme'] !== 'redis') {
            throw new DsnFormatException(['`:url` is invalid, `:scheme` scheme is not recognized', 'url' => $url, 'scheme' => $parts['scheme']]);
        }

        $this->_host = isset($parts['host']) ? $parts['host'] : '127.0.0.1';
        $this->_port = isset($parts['port']) ? (int)$parts['port'] : 6379;

        if (isset($parts['path'])) {
            $path = trim($parts['path'], '/');
            if ($path !== '' && !is_numeric($path)) {
                throw new DsnFormatException(['`:url` url is invalid, `:db` db is not integer', 'url' => $url, 'db' => $path]);
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
    }

    /**
     * @throws \ManaPHP\Redis\ConnectionException
     * @throws \ManaPHP\Redis\AuthException
     */
    public function connect()
    {
        if ($this->_redis) {
            $this->close();
        }

        $redis = new \Redis();

        if ($this->_persistent) {
            if (!@$redis->pconnect($this->_host, $this->_port, $this->_timeout, $this->_db)) {
                throw new ConnectionException(['connect to `:url` failed', 'url' => $this->_url]);
            }
            try {
                $redis->ping();
            } catch (\Exception $exception) {
                throw new ConnectionException(['connect to `:url` failed', 'url' => $this->_url]);
            }
        } else {
            if (!@$redis->connect($this->_host, $this->_port, $this->_timeout, null, $this->_retry_interval)) {
                throw new ConnectionException(['connect to `:url` failed', 'url' => $this->_url]);
            }
        }

        if ($this->_auth !== '' && !$redis->auth($this->_auth)) {
            throw new AuthException(['`:auth` auth is wrong.', 'auth' => $this->_auth]);
        }

        if ($this->_db !== 0 && !$redis->select($this->_db)) {
            throw new RuntimeException(['select `:db` db failed', 'db' => $this->_db]);
        }

        $this->redis = $redis;
        $this->_lastIoTime = microtime(true);
    }

    public function close()
    {
        if ($this->_redis) {
            $this->_redis->close();
            $this->_redis = null;
            $this->_lastIoTime = null;
        }
    }

    public function ping()
    {
        if (!$this->_redis) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->connect();
        } else {
            try {
                $this->_redis->ping();
            } catch (\Exception  $exception) {
                try {
                    $this->connect();
                } catch (\Exception $exception) {
                    throw new ConnectionException(['connection failed: `:url`', 'url' => $this->_url]);
                }
            }
        }
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return bool|mixed
     * @throws \ManaPHP\Redis\ConnectionException
     */
    public function __call($name, $arguments)
    {
        if (!$this->_redis) {
            $this->connect();
        } else {
            if (microtime(true) - $this->_lastIoTime > 1.0) {
                try {
                    $this->_redis->ping();
                    $this->_lastIoTime = microtime(true);
                } catch (\Exception $exception) {
                    null;
                }
            }
        }

        $this->_lastIoTime = microtime(true);

        if (stripos(',blPop,brPop,brpoplpush,subscribe,psubscribe,', ",$name,") !== false) {
            $this->logger->debug(["\$redis->$name(:args) ... blocking",
                'args' => substr(json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1),
            ], 'redis.' . $name);
        }

        switch (count($arguments)) {
            case 0:
                $r = $this->_redis->$name();
                break;
            case 1:
                $r = $this->_redis->$name($arguments[0]);
                break;
            case 2:
                $r = $this->_redis->$name($arguments[0], $arguments[1]);
                break;
            case 3:
                $r = $this->_redis->$name($arguments[0], $arguments[1], $arguments[2]);
                break;
            case 4:
                $r = $this->_redis->$name($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
                break;
            case 5:
                $r = $this->_redis->$name($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
                break;
            default:
                $r = call_user_func_array([$this->_redis, $name], $arguments);
                break;
        }
        $this->logger->debug(["\$redis->$name(:args) => :return",
            'args' => substr(json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1),
            'return' => json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ], 'redis.' . $name);

        return $r;
    }
}