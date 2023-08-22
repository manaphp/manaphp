<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Exception\DsnFormatException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Redis\Event\RedisClose;
use ManaPHP\Redis\Event\RedisConnected;
use ManaPHP\Redis\Event\RedisConnecting;
use ManaPHP\Redis\Exception as RedisException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Redis;

class Connection
{
    #[Inject] protected EventDispatcherInterface $eventDispatcher;
    #[Inject] protected MakerInterface $maker;

    #[Value] protected string $uri;

    protected bool $cluster = false;
    protected string $host;
    protected int $port;
    protected float $timeout = 0.0;
    protected int $read_timeout = 60;
    protected string $auth = '';
    protected int $db = 0;
    protected bool $persistent = false;
    protected int $heartbeat = 60;
    protected ?Redis $redis = null;
    protected ?float $last_heartbeat = null;
    protected bool $multi = false;

    public function __construct()
    {
        $parts = parse_url($this->uri);

        if (!in_array($parts['scheme'], ['redis', 'cluster'], true)) {
            throw new DsnFormatException(
                ['`%s` is invalid, `%s` scheme is not recognized', $this->uri, $parts['scheme']]
            );
        }

        $this->host = $parts['host'] ?? '127.0.0.1';
        $this->port = isset($parts['port']) ? (int)$parts['port'] : 6379;

        if (isset($parts['path'])) {
            $path = trim($parts['path'], '/');
            if ($path !== '' && !is_numeric($path)) {
                throw new DsnFormatException(['`%s` is invalid, `%s` db is not integer', $this->uri, $path]);
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

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getConnect(): Redis
    {
        if ($this->redis === null) {
            $uri = $this->uri;

            $this->eventDispatcher->dispatch(new RedisConnecting($this, $uri));

            if ($this->cluster) {
                $seeds = [];
                foreach (explode(',', $this->host) as $host) {
                    $seeds[] = str_contains($host, ':') ? $host : "$host:6379";
                }
                $redis = $this->maker->make(
                    'RedisCluster',
                    [null, $seeds, $this->timeout, $this->read_timeout, $this->persistent, $this->auth]
                );
            } else {
                $redis = $this->maker->make('Redis');

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

            $this->eventDispatcher->dispatch(new RedisConnected($this, $uri, $redis));

            $this->redis = $redis;
        }

        return $this->redis;
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    protected function ping(): bool
    {
        try {
            $this->redis->echo('OK');
            return true;
        } catch (\Exception  $exception) {
            return false;
        }
    }

    public function close(): void
    {
        if ($this->redis) {
            $this->eventDispatcher->dispatch(new RedisClose($this, $this->uri, $this->redis));

            $this->redis->close();
            $this->redis = null;
            $this->last_heartbeat = null;
            $this->multi = false;
        }
    }

    public function call(string $name, array $arguments): mixed
    {
        $redis = $this->getConnect();

        $read_timeout = null;
        if (in_array($name, ['subscribe', 'psubscribe'], true)) {
            $read_timeout = $redis->getOption(Redis::OPT_READ_TIMEOUT);
            $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        }

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
        } finally {
            if ($read_timeout !== null) {
                $redis->setOption(Redis::OPT_READ_TIMEOUT, $read_timeout);
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

    public function getLastHeartbeat(): ?float
    {
        return $this->last_heartbeat;
    }
}
