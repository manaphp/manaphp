<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Attribute;

interface MappingInterface
{
    public function getPath(): string|array|null;

    public function getMethod(): string;
}