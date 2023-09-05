<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface HandlerInterface
{
    public function handle(array $args): int;

    public function getEntrypoint(): string;

    public function getCommand(): string;

    public function getAction(): string;

    public function getParams(): array;
}