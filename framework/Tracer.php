<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Logging\LoggerInterface;
use Psr\Log\LogLevel;

abstract class Tracer
{
    #[Inject] protected LoggerInterface $logger;

    #[Value] protected bool $verbose = false;

    public function debug(mixed $message, string $category): void
    {
        $this->logger->log(LogLevel::DEBUG, $message, $category);
    }

    public function info(mixed $message, string $category): void
    {
        $this->logger->log(LogLevel::INFO, $message, $category);
    }

    public function warning(mixed $message, string $category): void
    {
        $this->logger->log(LogLevel::WARNING, $message, $category);
    }

    public function error(mixed $message, string $category): void
    {
        $this->logger->log(LogLevel::ERROR, $message, $category);
    }

    public function critical(mixed $message, string $category): void
    {
        $this->logger->log(LogLevel::CRITICAL, $message, $category);
    }
}