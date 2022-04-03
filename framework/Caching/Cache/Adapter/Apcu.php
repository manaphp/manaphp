<?php
declare(strict_types=1);

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\AbstractCache;
use ManaPHP\Exception\RuntimeException;

class Apcu extends AbstractCache
{
    protected string $prefix;

    public function __construct(string $prefix = 'cache:')
    {
        $this->prefix = $prefix;
    }

    public function do_exists(string $key): bool
    {
        return apcu_exists($this->prefix . $key);
    }

    public function do_get(string $key): false|string
    {
        return apcu_fetch($this->prefix . $key);
    }

    public function do_set(string $key, string $value, int $ttl): void
    {
        $r = apcu_store($this->prefix . $key, $value, $ttl);
        if (!$r) {
            throw new RuntimeException(['apcu_store failed for `:key` key', 'key' => $key]);
        }
    }

    public function do_delete(string $key): void
    {
        apcu_delete($this->prefix . $key);
    }
}