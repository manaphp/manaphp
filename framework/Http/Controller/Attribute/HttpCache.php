<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class HttpCache
{
    public array $headers = [];

    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }
}