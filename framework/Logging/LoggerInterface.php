<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

interface LoggerInterface extends PsrLoggerInterface
{
    public function log($level, mixed $message, array $context = []): void;

    public function debug(mixed $message, array $context = []): void;

    public function info(mixed $message, array $context = []): void;

    public function notice(mixed $message, array $context = []): void;

    public function warning(mixed $message, array $context = []): void;

    public function error(mixed $message, array $context = []): void;

    public function critical(mixed $message, array $context = []): void;

    public function alert(mixed $message, array $context = []): void;

    public function emergency(mixed $message, array $context = []): void;
}