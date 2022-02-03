<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

class PageCacheMiddlewareContext
{
    public ?int $ttl = null;
    public string $key;
    public string $if_none_match;
    public bool $cache_used = false;
}