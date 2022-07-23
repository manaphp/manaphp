<?php
declare(strict_types=1);

namespace ManaPHP\Invoking;

interface ArgumentsResolverInterface
{
    public function resolve(object $controller, string $method): array;
}