<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

interface MatcherInterface
{
    public function getHandler(): string;

    public function getParams(): array;
}