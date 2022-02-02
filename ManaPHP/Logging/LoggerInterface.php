<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

interface LoggerInterface
{
    public function setLevel(string $level): static;

    public function getLevel(): string;

    public function setLazy(bool $lazy = true): static;

    public function debug(mixed $message, ?string $category = null): static;

    public function info(mixed $message, ?string $category = null): static;

    public function warning(mixed $message, ?string $category = null): static;

    public function error(mixed $message, ?string $category = null): static;

    public function critical(mixed $message, ?string $category = null): static;
}