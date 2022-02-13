<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Controller;

use ManaPHP\Rpc\Controller;

interface ArgumentsResolverInterface
{
    public function resolve(Controller $controller, string $method): array;
}