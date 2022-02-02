<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface ConsoleInterface
{
    public function isSupportColor(): bool;

    public function colorize(string $text, int $options = 0, int $width = 0): string;

    public function sampleColorizer(): void;

    public function write(mixed $message, int $options = 0): static;

    public function writeLn(mixed $message = '', int $options = 0): static;

    public function debug(mixed $message = '', int $options = 0): static;

    public function info(mixed $message): void;

    public function warning(mixed $message): void;

    public function success(mixed $message): void;

    public function error(mixed $message, int $code = 1): int;

    public function progress(mixed $message, mixed $value = null): void;

    public function read(): string;

    public function ask(string $message): string;
}