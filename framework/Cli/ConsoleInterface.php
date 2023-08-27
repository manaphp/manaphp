<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use Stringable;

interface ConsoleInterface
{
    public function isSupportColor(): bool;

    public function colorize(string $text, int $options = 0, int $width = 0): string;

    public function sampleColorizer(): void;

    public function write(string|Stringable $message, array $context = [], int $options = 0): void;

    public function writeLn(string|Stringable $message = '', array $context = [], int $options = 0): void;

    public function debug(string|Stringable $message = '', array $context = [], int $options = 0): void;

    public function info(string|Stringable $message, array $context = []): void;

    public function warning(string|Stringable $message, array $context = []): void;

    public function success(string|Stringable $message, array $context = []): void;

    public function error(string|Stringable $message, array $context = [], int $code = 1): int;

    public function progress(string|Stringable $message, mixed $value = null): void;

    public function read(): string;

    public function ask(string $message): string;
}