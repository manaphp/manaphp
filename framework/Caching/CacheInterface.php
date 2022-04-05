<?php
declare(strict_types=1);

namespace ManaPHP\Caching;

use ManaPHP\Di\Attribute\Primary;

#[Primary('ManaPHP\Caching\Cache\Adapter\Redis')]
interface CacheInterface
{
    public function exists(string $key): bool;

    public function get(string $key): false|string;

    public function set(string $key, string $value, int $ttl): void;

    public function delete(string $key): void;

    public function remember(string $key, int $ttl, callable $callback): mixed;
}