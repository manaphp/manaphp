<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface HandlerInterface
{
    public function handle(?array $args = null): int;

    public function getArgs(): array;

    public function getCommand(): string;

    public function getAction(): string;

    public function getParams(): array;
}