<?php

namespace ManaPHP\Http\Middlewares;

class PageCacheMiddlewareContext
{
    public $ttl;
    public $key;
    public $if_none_match;
    public $cache_used;
}