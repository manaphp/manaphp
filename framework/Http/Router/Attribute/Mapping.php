<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Attribute;

class Mapping
{
    public ?string $method;

    public function __construct(public ?string $path = null)
    {

    }
}