<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Event\Listener;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
abstract class Tracer extends Listener
{
    protected bool $verbose;

    public function __construct(bool $verbose = false)
    {
        $this->verbose = $verbose;
    }

    public function debug(mixed $message, string $category): void
    {
        $this->logger->debug($message, $category);
    }

    public function info(mixed $message, string $category): void
    {
        $this->logger->info($message, $category);
    }

    public function warning(mixed $message, string $category): void
    {
        $this->logger->warning($message, $category);
    }

    public function error(mixed $message, string $category): void
    {
        $this->logger->error($message, $category);
    }

    public function critical(mixed $message, string $category): void
    {
        $this->logger->critical($message, $category);
    }
}