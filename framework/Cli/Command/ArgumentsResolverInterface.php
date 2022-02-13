<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Command;

use ManaPHP\Cli\Command;

interface ArgumentsResolverInterface
{
    public function resolve(Command $command, string $method): array;
}