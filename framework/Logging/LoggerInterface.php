<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

interface LoggerInterface
{
    public function debug(mixed $message, ?string $category = null): static;

    public function info(mixed $message, ?string $category = null): static;

    public function notice(mixed $message, ?string $category = null): static;

    public function warning(mixed $message, ?string $category = null): static;

    public function error(mixed $message, ?string $category = null): static;

    public function critical(mixed $message, ?string $category = null): static;

    public function alert(mixed $message, ?string $category = null): static;

    public function emergency(mixed $message, ?string $category = null): static;
}