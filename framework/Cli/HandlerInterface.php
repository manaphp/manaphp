<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface HandlerInterface
{
    public function handle(string $command, string $action, array $params): int;
}