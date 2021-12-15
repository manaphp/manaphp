<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

interface LoggerInterface
{
    public function setLevel(int|string $level): static;

    public function getLevel(): int;

    public function getLevels(): array;

    public function setLazy(bool $lazy = true): static;

    public function debug(mixed $message, ?string $category = null): static;

    public function info(mixed $message, ?string $category = null): static;

    public function warn(mixed $message, ?string $category = null): static;

    public function error(mixed $message, ?string $category = null): static;

    public function fatal(mixed $message, ?string $category = null): static;
}