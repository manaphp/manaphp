<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use JsonSerializable;
use ManaPHP\AliasInterface;
use ManaPHP\Coroutine;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Logging\Logger\Event\LoggerLog;
use ManaPHP\Logging\Logger\Log;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;
use function dirname;
use function is_array;
use function is_object;
use function is_scalar;
use function is_string;
use function json_stringify;

abstract class AbstractLogger extends \Psr\Log\AbstractLogger
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected AliasInterface $alias;

    #[Autowired] protected string $level = LogLevel::DEBUG;
    #[Autowired] protected ?string $hostname;
    #[Autowired] protected string $time_format = 'Y-m-d\TH:i:s.uP';

    public const  MILLISECONDS = 'v';
    public const MICROSECONDS = 'u';

    abstract public function append(Log $log): void;

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

    protected function interpolateMessage(string $message, array $context): string
    {
        $replaces = [];
        preg_match_all('#{([\w.]+)}#', $message, $matches);
        foreach ($matches[1] as $key) {
            if (($val = $context[$key] ?? null) === null) {
                continue;
            }

            if (is_string($val)) {
                SuppressWarnings::noop();
            } elseif ($val instanceof JsonSerializable) {
                $val = json_stringify($val);
            } elseif ($val instanceof Stringable) {
                $val = (string)$val;
            } elseif (is_scalar($val)) {
                $val = json_stringify($val);
            } elseif (is_array($val)) {
                $val = json_stringify($val);
            } elseif (is_object($val)) {
                $val = json_stringify((array)$val);
            } else {
                continue;
            }

            $replaces['{' . $key . '}'] = $val;
        }
        return strtr($message, $replaces);
    }

    protected function formatMessage(mixed $message, array $context): string
    {
        if (is_string($message)) {
            if ($context !== [] && str_contains($message, '{')) {
                $message = $this->interpolateMessage($message, $context);
            }

            if (($exception = $context['exception'] ?? null) !== null && $exception instanceof Throwable) {
                $message .= ': ' . $this->exceptionToString($exception);
            }
            return $message;
        } elseif ($message instanceof Throwable) {
            return $this->exceptionToString($message);
        } elseif ($message instanceof Stringable) {
            return (string)$message;
        } else {
            return json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
    }

    protected function getCategory(mixed $message, array $context, array $traces): string
    {
        if (($v = $context['category'] ?? null) !== null
            && is_string($v)
            && (!is_string($message) || !str_contains($message, '{category}'))
        ) {
            return $v;
        } else {
            if (($v = $context['exception'] ?? null) !== null && $v instanceof Throwable) {
                $trace = $v->getTrace()[0];
            } elseif (isset($traces[1])) {
                $trace = $traces[1];
                if (str_ends_with($trace['function'], '{closure}')) {
                    $trace = $traces[2];
                }
            } else {
                $trace = $traces[0];
            }
            if (isset($trace['class'])) {
                return str_replace('\\', '.', $trace['class']) . '.' . $trace['function'];
            } else {
                return $trace['function'];
            }
        }
    }

    public function log($level, mixed $message, array $context = []): void
    {
        $levels = Level::map();
        if ($levels[$level] > $levels[$this->level]) {
            return;
        }

        $log = new Log($level, $this->hostname ?? gethostname(), $this->time_format);

        $traces = Coroutine::getBacktrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);
        array_shift($traces);

        $log->category = $this->getCategory($message, $context, $traces);

        $log->setLocation($traces[0]);

        $log->message = $this->formatMessage($message, $context);

        $this->eventDispatcher->dispatch(new LoggerLog($this, $level, $message, $context, $log));

        $this->append($log);
    }
}