<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

interface PathsNormalizerInterface
{
    public function normalize(string|array $paths): array;
}