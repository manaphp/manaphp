<?php
declare(strict_types=1);

namespace ManaPHP\Caching;

use DateInterval;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Redis\RedisCacheInterface;
use Psr\SimpleCache\CacheInterface;

class SimpleCache implements CacheInterface
{
    #[Autowired] protected RedisCacheInterface $redisCache;

    #[Autowired] protected string $prefix = 'cache:';

    public function get(string $key, mixed $default = null): mixed
    {
        if (($value = $this->redisCache->get($this->prefix . $key)) === false) {
            return $default;
        } else {
            return json_parse($value);
        }
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return $this->redisCache->set($this->prefix . $key, json_stringify($value), $ttl);
    }

    public function delete(string $key): bool
    {
        $this->redisCache->del($this->prefix . $key);

        return true;
    }

    public function clear(): bool
    {
        if ($this->prefix === '') {
            $this->redisCache->flushDB();
        } else {
            $iterator = null;
            /** @noinspection PhpParamsInspection */
            while ([] !== ($keys = $this->redisCache->scan($iterator, $this->prefix . '*', 100))) {
                $this->redisCache->del($keys);
            }
        }

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $redis = $this->redisCache->pipeline();

        foreach ($keys as $key) {
            $redis->get($this->prefix . $key);
        }

        $values = [];
        foreach ($redis->exec() as $i => $value) {
            $key = $keys[$i];
            $values[$key] = $value === false ? $default : json_parse($value);
        }

        return $values;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $redis = $this->redisCache->pipeline();

        foreach ($values as $key => $value) {
            $redis->set($this->prefix . $key, json_stringify($value), $ttl);
        }

        $redis->exec();

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $keys2 = [];
        foreach ($keys as $key) {
            $keys2[] = $this->prefix . $key;
        }

        if (\count($keys2) !== 0) {
            $this->redisCache->del($keys2);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->redisCache->exists($this->prefix . $key);
    }
}