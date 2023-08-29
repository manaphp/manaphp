<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface RouterInterface
{
    public function route(array $args): void;

    public function getEntrypoint(): string;

    public function getCommand(): string;

    public function getAction(): string;

    public function getParams(): array;
}