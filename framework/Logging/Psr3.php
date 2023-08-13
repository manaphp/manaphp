<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use Stringable;
use Throwable;

class Psr3 extends Component implements \Psr\Log\LoggerInterface
{
    #[Inject]
    protected LoggerInterface $logger;

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
        $this->log(Level::DEBUG, $message, $context);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log(Level::INFO, $message, $context);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log(Level::NOTICE, $message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log(Level::WARNING, $message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log(Level::ERROR, $message, $context);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log(Level::CRITICAL, $message, $context);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log(Level::ALERT, $message, $context);
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log(Level::EMERGENCY, $message, $context);
    }
}