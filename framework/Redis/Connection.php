<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Redis\Event\RedisCalled;
use ManaPHP\Redis\Event\RedisCalling;
use ManaPHP\Redis\Event\RedisClose;
use ManaPHP\Redis\Event\RedisConnected;
use ManaPHP\Redis\Event\RedisConnecting;
use Psr\EventDispatcher\EventDispatcherInterface;
use Redis;
use RedisCluster;
use RedisException;

class Connection
{
    #[Inject] protected EventDispatcherInterface $eventDispatcher;
    #[Inject] protected MakerInterface $maker;

    #[Value] protected string $uri;

    protected ?Redis $redis = null;
    protected ?float $last_heartbeat = null;

    protected int $heartbeat;

    public function __construct()
    {
        if (preg_match('#heartbeat=(\d+)#', $this->uri, $match) === 1) {
            $this->heartbeat = (int)$match[1];
        } else {
            $this->heartbeat = 60;
        }
    }

    public function __clone()
    {
        $this->redis = null;
        $this->last_heartbeat = null;
    }

    protected function getRedisCluster(string $uri): RedisCluster
    {
        $seeds = [];
        foreach (explode(',', parse_url($uri, PHP_URL_HOST)) as $host) {
            $seeds[] = str_contains($host, ':') ? $host : "$host:6379";
        }

        parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);
        $timeout = isset($query['timeout']) ? (int)$query['timeout'] : 1;
        $persistent = MANAPHP_COROUTINE_ENABLED && isset($query['persistent']) && $query['persistent'] !== '0';
        $auth = $query['auth'] ?? null;

        return $this->maker->make(RedisCluster::class, [null, $seeds, $timeout, null, $persistent, $auth]);
    }

    protected function getRedis(string $uri): Redis
    {
        $host = parse_url($uri, PHP_URL_HOST) ?? '127.0.0.1';
        $port = parse_url($uri, PHP_URL_PORT) ?? 6379;

        parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);
        $timeout = isset($query['timeout']) ? (int)$query['timeout'] : 1;
        $persistent = MANAPHP_COROUTINE_ENABLED && isset($query['persistent']) && $query['persistent'] !== '0';

        $redis = $this->maker->make(Redis::class);
        $persistent_id = md5($uri);
        if ($persistent) {
            if (!@$redis->pconnect($host, (int)$port, $timeout, $persistent_id)) {
                throw new ConnectionException(['connect to `{uri}` failed', 'uri' => $uri]);
            }
        } else {
            if (!@$redis->connect($host, (int)$port, $timeout)) {
                throw new ConnectionException(['connect to `{uri}` failed', 'uri' => $uri]);
            }
        }

        if (($auth = $query['auth'] ?? '') !== '' && !$redis->auth($auth)) {
            throw new AuthException(['`{auth}` auth is wrong.', 'auth' => $auth]);
        }

        return $redis;
    }

    public function getConnect(): Redis|RedisCluster
    {
        if ($this->redis !== null && microtime(true) - $this->last_heartbeat > $this->heartbeat) {
            try {
                $this->redis->echo('heartbeat');
            } catch (RedisException) {
                $this->close();
            }
        }

        if ($this->redis === null) {
            $uri = $this->uri;

            $this->eventDispatcher->dispatch(new RedisConnecting($this, $uri));

            $scheme = parse_url($uri, PHP_URL_SCHEME);
            if ($scheme === 'cluster') {
                $redis = $this->getRedisCluster($uri);
            } elseif ($scheme === 'redis') {
                $redis = $this->getRedis($uri);
            } else {
                throw new NotSupportedException(sprintf('%s is not recognized', $uri));
            }

            parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);

            if (isset($query['db'])) {
                $db = (int)$query['db'];
            } elseif (preg_match('#/(\d+)#', parse_url($uri, PHP_URL_PATH) ?? '', $match) === 1) {
                $db = (int)$match[1];
            } else {
                $db = 0;
            }

            if ($db !== 0 && !$redis->select($db)) {
                throw new RuntimeException(['select `{db}` db failed', 'db' => $db]);
            }

            if (($read_timeout = $query['read_timeout'] ?? null) !== null) {
                $redis->setOption(Redis::OPT_READ_TIMEOUT, $read_timeout);
            }

            $this->eventDispatcher->dispatch(new RedisConnected($this, $uri, $redis));

            $this->redis = $redis;
        }

        $this->last_heartbeat = microtime(true);

        return $this->redis;
    }

    public function close(): void
    {
        if ($this->redis) {
            $this->eventDispatcher->dispatch(new RedisClose($this, $this->uri, $this->redis));

            $this->redis->close();
            $this->redis = null;
            $this->last_heartbeat = null;
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
            $return = @$redis->$name(...$arguments);
        } finally {
            if ($read_timeout !== null) {
                $redis->setOption(Redis::OPT_READ_TIMEOUT, $read_timeout);
            }
        }

        return $return;
    }

    public function __call(string $method, array $arguments): mixed
    {
        $this->eventDispatcher->dispatch(new RedisCalling($this, $method, $arguments));

        $return = $this->call($method, $arguments);

        $this->eventDispatcher->dispatch(new RedisCalled($this, $method, $arguments, $return));

        return $return;
    }
}
