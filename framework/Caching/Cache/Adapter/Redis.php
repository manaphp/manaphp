<?php
declare(strict_types=1);

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\AbstractCache;

/**
 * @property-read \ManaPHP\ConfigInterface          $config
 * @property-read \ManaPHP\Data\RedisCacheInterface $redisCache
 */
class Redis extends AbstractCache
{
    protected string $prefix;

    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ?? sprintf('cache:%s:', $this->config->get('id'));
    }

    public function do_get(string $key): false|string
    {
        return $this->redisCache->get($this->prefix . $key);
    }

    public function do_set(string $key, string $value, int $ttl): void
    {
        $this->redisCache->set($this->prefix . $key, $value, $ttl);
    }

    public function do_delete(string $key): void
    {
        $this->redisCache->del($this->prefix . $key);
    }

    public function do_exists(string $key): bool
    {
        return (bool)$this->redisCache->exists($this->prefix . $key);
    }
}
