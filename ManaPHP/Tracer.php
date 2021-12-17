<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Event\Listener;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
abstract class Tracer extends Listener
{
    protected bool $verbose = false;

    public function __construct(array $options = [])
    {
        if (isset($options['verbose'])) {
            $this->verbose = (bool)$options['verbose'];
        }
    }

    public function debug(mixed $message, string $category): void
    {
        $this->logger->debug($message, $category);
    }

    public function info(mixed $message, string $category): void
    {
        $this->logger->info($message, $category);
    }

    public function warn(mixed $message, string $category): void
    {
        $this->logger->warn($message, $category);
    }

    public function error(mixed $message, string $category): void
    {
        $this->logger->error($message, $category);
    }

    public function fatal(mixed $message, string $category): void
    {
        $this->logger->fatal($message, $category);
    }
}