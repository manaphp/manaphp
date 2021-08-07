<?php

namespace ManaPHP\Data\Redis;

use ManaPHP\Component;
use ManaPHP\Data\Redis\Exception as RedisException;
use ManaPHP\Exception\DsnFormatException;
use ManaPHP\Exception\RuntimeException;
use Redis;
use Throwable;

class Connection extends Component
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var bool
     */
    protected $cluster = false;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var float
     */
    protected $timeout = 0.0;

    /**
     * @var int
     */
    protected $read_timeout = 60;

    /**
     * @var string
     */
    protected $auth;

    /**
     * @var int
     */
    protected $db = 0;

    /**
     * @var bool
     */
    protected $persistent = false;

    /**
     * @var int
     */
    protected $heartbeat = 60;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var float
     */
    protected $last_heartbeat;

    /**
     * @var bool
     */
    protected $multi = false;

    /**
     * @param string|\ManaPHP\Data\Redis\Connection $uri
     *
     * @throws \ManaPHP\Exception\DsnFormatException
     */
    public function __construct($uri)
    {
        $this->uri = $uri;

        $parts = parse_url($uri);

        if ($parts['scheme'] === 'redis') {
            $this->host = $parts['host'] ?? '127.0.0.1';
            $this->port = isset($parts['port']) ? (int)$parts['port'] : 6379;
        } elseif ($parts['scheme'] === 'cluster') {
            $this->cluster = true;
            $this->host = $parts['host'] . ':' . ($parts['port'] ?? '6379');
        } else {
            throw new DsnFormatException(['`%s` is invalid, `%s` scheme is not recognized', $uri, $parts['scheme']]);
        }

        $this->host = $parts['host'] ?? '127.0.0.1';
        $this->port = isset($parts['port']) ? (int)$parts['port'] : 6379;

        if (isset($parts['path'])) {
            $path = trim($parts['path'], '/');
            if ($path !== '' && !is_numeric($path)) {
                throw new DsnFormatException(['`%s` is invalid, `%s` db is not integer', $uri, $path]);
            }
            $this->db = (int)$path;
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            if (isset($query['db'])) {
                $this->db = (int)$query['db'];
            }

            if (isset($query['auth'])) {
                $this->auth = $query['auth'];
            }

            if (isset($query['timeout'])) {
                $this->timeout = (float)$query['timeout'];
            }

            if (isset($query['read_timeout'])) {
                $this->read_timeout = $query['read_timeout'];
            }

            if (isset($query['persistent'])) {
                $this->persistent = !MANAPHP_COROUTINE_ENABLED && $query['persistent'] === '1';
            }

            if (isset($query['heartbeat'])) {
                $this->heartbeat = $query['heartbeat'];
            }
        }
    }

    public function __clone()
    {
        $this->redis = null;
        $this->last_heartbeat = null;
        $this->multi = false;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return \Redis
     */
    public function getConnect()
    {
        if ($this->redis === null) {
            $uri = $this->uri;

            $this->fireEvent('redis:connecting', compact('uri'));

            if ($this->cluster) {
                $seeds = [];
                foreach (explode(',', $this->host) as $host) {
                    $seeds[] = str_contains($host, ':') ? $host : "$host:6379";
                }
                $redis = $this->getNew(
                    'RedisCluster',
                    [null, $seeds, $this->timeout, $this->read_timeout, $this->persistent, $this->auth]
                );
            } else {
                $redis = $this->getNew('Redis');

                if ($this->persistent) {
                    if (!@$redis->pconnect($this->host, $this->port, $this->timeout, $this->db)) {
                        throw new ConnectionException(['connect to `:uri` failed', 'uri' => $this->uri]);
                    }
                } elseif (!@$redis->connect($this->host, $this->port, $this->timeout)) {
                    throw new ConnectionException(['connect to `:uri` failed', 'uri' => $this->uri]);
                }

                if ($this->auth && !$redis->auth($this->auth)) {
                    throw new AuthException(['`:auth` auth is wrong.', 'auth' => $this->auth]);
                }

                if ($this->db !== 0 && !$redis->select($this->db)) {
                    throw new RuntimeException(['select `:db` db failed', 'db' => $this->db]);
                }

                $redis->setOption(Redis::OPT_READ_TIMEOUT, $this->read_timeout);
            }

            $this->fireEvent('redis:connected', compact('uri', 'redis'));

            $this->redis = $redis;
        }

        return $this->redis;
    }

    /**
     * @return bool
     */
    protected function ping()
    {
        try {
            $this->redis->echo('OK');
            return true;
        } catch (\Exception  $exception) {
            return false;
        }
    }

    /**
     * @return void
     */
    public function close()
    {
        if ($this->redis) {
            $uri = $this->uri;
            $redis = $this->redis;
            $this->fireEvent('redis:close', compact('uri', 'redis'));

            $this->redis->close();
            $this->redis = null;
            $this->last_heartbeat = null;
            $this->multi = false;
        }
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function call($name, $arguments)
    {
        $redis = $this->getConnect();

        try {
            $r = @$redis->$name(...$arguments);
        } catch (\Exception  $exception) {
            $r = null;
            $failed = true;
            if (!$this->multi && !$this->ping()) {
                $this->close();
                $this->getConnect();

                try {
                    $r = @$redis->$name(...$arguments);
                    $failed = false;
                } catch (\RedisException $exception) {
                }
            }

            if ($failed) {
                $this->multi = false;
                throw new RedisException($exception);
            }
        }

        if ($name === 'multi') {
            $this->multi = true;
        } elseif ($name === 'exec' || $name === 'discard') {
            $this->multi = false;
        }

        $this->last_heartbeat = microtime(true);

        return $r;
    }

    /**
     * @return float|null
     */
    public function getLastHeartbeat()
    {
        return $this->last_heartbeat;
    }
}
