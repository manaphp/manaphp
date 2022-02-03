<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

interface RouteInterface
{
    public function match(string $uri, string $method = 'GET'): false|array;
}