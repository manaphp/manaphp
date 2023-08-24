<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\AliasInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Coroutine;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Logging\Logger\Event\LoggerLog;
use ManaPHP\Logging\Logger\Log;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

abstract class AbstractLogger implements LoggerInterface
{
    use ContextTrait;

    #[Inject] protected EventDispatcherInterface $eventDispatcher;
    #[Inject] protected AliasInterface $alias;
    #[Inject] protected RequestInterface $request;

    #[Value] protected string $level = LogLevel::DEBUG;
    #[Value] protected ?string $hostname;
    #[Value] protected string $time_format = 'Y-m-d\TH:i:s.uP';

    protected const  MILLISECONDS = 'v';
    protected const MICROSECONDS = 'u';

    abstract public function append(Log $log): void;

    protected function getLocation(array $traces): array
    {
        if (($object = $traces[0]['object'] ?? null) !== null && $object instanceof PsrLoggerInterface) {
            return $traces[1];
        } else {
            return $traces[0];
        }
    }

    protected function inferCategory(array $traces): string
    {
        if (($object = $traces[1]['object'] ?? null) !== null && $object instanceof PsrLoggerInterface) {
            $index = 2;
        } else {
            $index = 1;
        }
        $trace = $traces[$index];
        if (str_ends_with($trace['function'], '{closure}')) {
            $trace = $traces[$index + 1];
        }

        if (isset($trace['class'])) {
            return str_replace('\\', '.', $trace['class']) . '.' . $trace['function'];
        } else {
            return $trace['function'];
        }
    }

    public function exceptionToString(Throwable $exception): string
    {
        $str = $exception::class . ': ' . $exception->getMessage() . PHP_EOL;
        $str .= '    at ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
        $traces = $exception->getTraceAsString();
        $str .= preg_replace('/#\d+\s/', '    at ', $traces);

        $prev = $traces;
        $caused = $exception;
        while ($caused = $caused->getPrevious()) {
            $str .= PHP_EOL . '  Caused by ' . $caused::class . ': ' . $caused->getMessage() . PHP_EOL;
            $str .= '    at ' . $caused->getFile() . ':' . $caused->getLine() . PHP_EOL;
            $traces = $exception->getTraceAsString();
            if ($traces !== $prev) {
                $str .= preg_replace('/#\d+\s/', '    at ', $traces);
            } else {
                $str .= '    at ...';
            }

            $prev = $traces;
        }

        $replaces = [];
        if ($this->alias->has('@root')) {
            $replaces[dirname(realpath($this->alias->get('@root'))) . DIRECTORY_SEPARATOR] = '';
        }

        return strtr($str, $replaces);
    }

    public function formatMessage(mixed $message): string
    {
        if (is_string($message)) {
            return $message;
        } elseif ($message instanceof Throwable) {
            return $this->exceptionToString($message);
        } else {
            return json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
    }

    public function log(string $level, mixed $message, ?string $category = null): static
    {
        $levels = Level::map();
        if ($levels[$level] > $levels[$this->level]) {
            return $this;
        }

        if ($category !== null && !is_string($category)) {
            $message = [$message . ': :param', 'param' => $category];
            $category = null;
        }

        if (is_array($message) && count($message) === 1 && isset($message[0])) {
            $message = $message[0];
        }

        $log = new Log();

        $log->hostname = $this->hostname ?? gethostname();
        $log->level = strtoupper($level);

        if ($message instanceof Throwable) {
            $log->category = $category ?: 'exception';
            $log->file = basename($message->getFile());
            $log->line = $message->getLine();
        } else {
            $traces = Coroutine::getBacktrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);
            array_shift($traces);
            if ($category !== null && $category[0] === '.') {
                $log->category = $this->inferCategory($traces) . $category;
            } else {
                $log->category = $category ?: $this->inferCategory($traces);
            }

            $location = $this->getLocation($traces);
            if (isset($location['file'])) {
                $log->file = basename($location['file']);
                $log->line = $location['line'];
            } else {
                $log->file = '';
                $log->line = 0;
            }
        }

        $log->location = $log->file . ':' . $log->line;

        $log->message = is_string($message) ? $message : $this->formatMessage($message);
        $log->timestamp = microtime(true);
        $time_format = $this->time_format;
        if (str_contains($time_format, self::MILLISECONDS)) {
            $ms = sprintf('%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
            $time_format = str_replace(self::MILLISECONDS, $ms, $time_format);
        } elseif (str_contains($time_format, self::MICROSECONDS)) {
            $ms = sprintf('%06d', ($log->timestamp - (int)$log->timestamp) * 1000000);
            $time_format = str_replace(self::MICROSECONDS, $ms, $time_format);
        }

        $log->time = date($time_format, (int)$log->timestamp);

        $this->eventDispatcher->dispatch(new LoggerLog($this, $level, $message, $category, $log));

        $this->append($log);

        return $this;
    }

    public function debug(mixed $message, ?string $category = null): static
    {
        return $this->log(LogLevel::DEBUG, $message, $category);
    }

    public function info(mixed $message, ?string $category = null): static
    {
        return $this->log(LogLevel::INFO, $message, $category);
    }

    public function notice(mixed $message, ?string $category = null): static
    {
        return $this->log(LogLevel::NOTICE, $message, $category);
    }

    public function warning(mixed $message, ?string $category = null): static
    {
        return $this->log(LogLevel::WARNING, $message, $category);
    }

    public function error(mixed $message, ?string $category = null): static
    {
        return $this->log(LogLevel::ERROR, $message, $category);
    }

    public function critical(mixed $message, ?string $category = null): static
    {
        return $this->log(LogLevel::CRITICAL, $message, $category);
    }

    public function alert(mixed $message, ?string $category = null): static
    {
        return $this->log(LogLevel::ALERT, $message, $category);
    }

    public function emergency(mixed $message, ?string $category = null): static
    {
        return $this->log(LogLevel::EMERGENCY, $message, $category);
    }
}