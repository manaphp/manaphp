<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Action;

interface ArgumentsResolverInterface extends \ManaPHP\Invoking\ArgumentsResolverInterface
{
    public function resolve(object $controller, string $method): array;
}