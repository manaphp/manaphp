<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PostMapping extends Mapping
{
    public function getMethod(): string
    {
        return 'POST';
    }
}