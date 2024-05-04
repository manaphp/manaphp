<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Attribute;

abstract class Mapping implements MappingInterface
{
    public string|array|null $path;

    public function __construct(string|array|null $path = null)
    {
        $this->path = $path;
    }

    public function getPath(): string|array|null
    {
        return $this->path;
    }
}