<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface DispatcherInterface
{
    public function dispatch(string $command, string $action, array $params): int;
}