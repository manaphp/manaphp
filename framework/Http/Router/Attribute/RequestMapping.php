<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RequestMapping extends Mapping
{
    public function __construct(string|array $path)
    {
        parent::__construct($path);
    }

    public function getMethod(): string
    {
        return '*';
    }
}