<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Http\AbstractSession;

/**
 * @property-read \ManaPHP\ConfigInterface          $config
 * @property-read \ManaPHP\Data\RedisCacheInterface $redisCache
 */
class Redis extends AbstractSession
{
    protected string $prefix;

    public function __construct(?string $prefix = null,
        int $ttl = 3600, int $lazy = 60, string $name = "PHPSESSID",
        string $serializer = 'json', array $params = []
    ) {
        parent::__construct($ttl, $lazy, $name, $serializer, $params);

        $this->prefix = $prefix ?? sprintf("cache:%s:session:", $this->config->get("id"));
    }

    public function do_read(string $session_id): string
    {
        $data = $this->redisCache->get($this->prefix . $session_id);
        return is_string($data) ? $data : '';
    }

    public function do_write(string $session_id, string $data, int $ttl): bool
    {
        return $this->redisCache->set($this->prefix . $session_id, $data, $ttl);
    }

    public function do_touch(string $session_id, int $ttl): bool
    {
        $this->redisCache->expire($this->prefix . $session_id, $ttl);

        return true;
    }

    public function do_destroy(string $session_id): void
    {
        $this->redisCache->del($this->prefix . $session_id);
    }

    public function do_gc(int $ttl): void
    {
    }
}
