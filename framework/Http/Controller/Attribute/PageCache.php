<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PageCache
{
    public int $ttl;
    public ?array $key;

    public function __construct(int $ttl = 3, ?array $key = null)
    {
        $this->ttl = $ttl;
        $this->key = $key;
    }
}