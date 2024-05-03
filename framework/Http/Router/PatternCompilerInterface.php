<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

interface PatternCompilerInterface
{
    public function compile(string $pattern): string;
}