<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

class PageCacheFilterContext
{
    public ?int $ttl = null;
    public string $key;
    public string $if_none_match;
    public bool $cache_used = false;
}