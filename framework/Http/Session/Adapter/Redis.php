<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AbstractSession;
use ManaPHP\Redis\RedisCacheInterface;

class Redis extends AbstractSession
{
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected RedisCacheInterface $redisCache;

    #[Autowired] protected ?string $prefix;

    protected function getKey(string $session_id): string
    {
        return ($this->prefix ?? sprintf("cache:%s:session:", $this->config->get("id"))) . $session_id;
    }

    public function do_read(string $session_id): string
    {
        $data = $this->redisCache->get($this->getKey($session_id));
        return is_string($data) ? $data : '';
    }

    public function do_write(string $session_id, string $data, int $ttl): bool
    {
        return $this->redisCache->set($this->getKey($session_id), $data, $ttl);
    }

    public function do_touch(string $session_id, int $ttl): bool
    {
        $this->redisCache->expire($this->getKey($session_id), $ttl);

        return true;
    }

    public function do_destroy(string $session_id): void
    {
        $this->redisCache->del($this->getKey($session_id));
    }

    public function do_gc(int $ttl): void
    {
    }
}
