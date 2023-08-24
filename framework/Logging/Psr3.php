<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Di\Attribute\Inject;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;

class Psr3 implements \Psr\Log\LoggerInterface
{
    #[Inject] protected LoggerInterface $logger;

    public function log($level, Stringable|string $message, array $context = []): void
    {
        if (($exception = $context['exception'] ?? null) !== null && $exception instanceof Throwable) {
            $context['exception'] = '';
            $this->log($level, $message, $context);
            $this->logger->log($level, $exception, 'exception');
        } else {
            $replace = [];
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = is_string($val) ? $val : json_stringify($val);
            }

            $this->logger->log($level, strtr($message, $replace));
        }
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
}