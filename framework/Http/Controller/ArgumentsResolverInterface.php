<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

use ManaPHP\Http\Controller;

interface ArgumentsResolverInterface
{
    public function resolve(Controller $controller, string $method): array;
}