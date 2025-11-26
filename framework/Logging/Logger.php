<?php

declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Coroutine;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Logging\Appender\FileAppender;
use ManaPHP\Logging\Event\LoggerLog;
use ManaPHP\Logging\Message\Categorizable;
use ManaPHP\Text\InterpolatingFormatterInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;
use function array_shift;
use function gethostname;
use function json_stringify;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function strlen;
use function strrpos;
use function substr;

class Logger extends AbstractLogger
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected InterpolatingFormatterInterface $interpolatingFormatter;
    #[Autowired] protected AppenderFactory $appenderFactory;

    #[Autowired] protected string $level = LogLevel::DEBUG;
    #[Autowired] protected array $levels = [];
    #[Autowired] protected ?string $hostname;
    #[Autowired] protected string $time_format = 'Y-m-d\TH:i:s.uP';
    #[Autowired] protected array $appenders = [FileAppender::class];

    public const  MILLISECONDS = 'v';
    public const MICROSECONDS = 'u';

    protected function getCategory(array $context, array $traces): string
    {
        // Priority 1: If context contains an exception, use its trace to get the category
        // This ensures exceptions are categorized by where they were thrown, not where they were logged
        if (($v = $context['exception'] ?? null) !== null && $v instanceof Throwable) {
            $trace = $v->getTrace()[0] ?? null;
        } elseif (isset($traces[1])) {
            // Priority 2: Use traces[1] (skip traces[0] which is this logger method itself)
            $trace = $traces[1];
            // If the function is a closure, skip it and use the caller of the closure
            // This prevents anonymous functions from appearing in the category
            if (str_ends_with($trace['function'] ?? '', '{closure}')) {
                $trace = $traces[2] ?? null;
            }
        } else {
            // Fallback: Use traces[0] if traces[1] doesn't exist
            $trace = $traces[0] ?? null;
        }

        // Format category as "Class.method" for class methods, or "function" for functions
        if (isset($trace['class'])) {
            // Convert namespace separators to dots for consistency
            return str_replace('\\', '.', $trace['class']) . '.' . ($trace['function'] ?? '');
        } else {
            // Global function or no trace available
            return $trace['function'] ?? 'unknown';
        }
    }

    protected function getCategoryLevel(string $category): string
    {
        if (($level = $this->levels[$category] ?? null) !== null) {
            return $level;
        }

        $prev = 0;
        $len = strlen($category);
        // Search from right to left for dots, traversing up the category hierarchy
        // e.g., "A.B.C.D" -> "A.B.C" -> "A.B" -> "A"
        while (($next = strrpos($category, '.', $prev)) !== false && $next > 0) {
            $s = substr($category, 0, $next);
            if (($level = $this->levels[$s] ?? null) !== null) {
                return $level;
            }
            // When strrpos's third parameter is negative, it searches from the end of the string
            // offset by abs($prev) characters. $next - $len converts absolute position to
            // negative offset from the end, and -1 ensures the next search starts before
            // the found dot to avoid finding the same position again.
            // Example: category="A.B.C.D"(len=7), found last dot at next=5,
            // then prev=5-7-1=-3, meaning search from 3 chars before the end (position 4) next time
            $prev = $next - $len - 1;
        }

        return $this->level;
    }

    protected function format(string|Stringable $message, array $context): string
    {
        // Special case: If message itself is a Throwable, format it as an exception
        // In PHP 8.0+, Throwable implements Stringable (via __toString() method),
        // so this check is valid and allows special handling for exceptions with full stack traces
        if ($message instanceof Throwable) {
            $str = $context === [] ? '' : json_stringify($context);
            return $str . PHP_EOL . $this->interpolatingFormatter->exceptionToString($message);
        }

        // Extract exception from context if present (PSR-3 convention: exception key)
        // The exception will be formatted separately with full stack trace
        if (($exception = $context['exception'] ?? null) !== null && $exception instanceof Throwable) {
            unset($context['exception']);
        } else {
            $exception = null;
        }

        // Separate context into placeholders (used in message) and extra data (appended as JSON)
        // Placeholders are in format {key} in the message string
        // Context keys that don't appear as placeholders are treated as extra data
        $extra = [];
        // When $context is not empty, $message is guaranteed to be a string by convention
        // (Stringable objects are converted to string before calling format with context)
        foreach ($context as $key => $value) {
            // Check if this key is used as a placeholder in the message (e.g., "User {user_id} logged in")
            if (!str_contains($message, "{{$key}}")) {
                // Not a placeholder, move to extra data
                $extra[$key] = $value;
                unset($context[$key]);
            }
        }

        // Replace placeholders in message with context values (e.g., {user_id} -> actual user ID)
        if ($context !== []) {
            $message = $this->interpolatingFormatter->interpolate($message, $context);
        }

        // Append extra context data as JSON for additional information
        // This allows passing extra data without cluttering the message format
        if ($extra !== []) {
            $message .= ' ' . json_stringify($extra);
        }

        // Append exception stack trace if present
        // This provides full debugging information for exceptions
        if ($exception !== null) {
            $message .= PHP_EOL . $this->interpolatingFormatter->exceptionToString($exception);
        }

        return $message;
    }

    public function log($level, mixed $message, array $context = []): void
    {
        // Early return: If no per-category levels are configured, use global level filter
        // Level::gt returns true if $level is greater (more verbose) than $this->level
        // Greater level number means less important (DEBUG=7 > INFO=6), so we skip less important logs
        if ($this->levels === [] && Level::gt($level, $this->level)) {
            return;
        }

        // Get backtrace to determine log category and location
        // Limit to 7 frames to avoid excessive memory usage
        $traces = Coroutine::getBacktrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);
        // Remove the first frame (this method itself) from traces
        array_shift($traces);

        // Determine category: use explicit category if message implements Categorizable,
        // otherwise auto-detect from call stack or exception trace
        if ($message instanceof Categorizable) {
            $category = $message->getCategory();
            $message = (string)$message;
        } else {
            $category = $this->getCategory($context, $traces);
        }

        // Per-category level filtering: if category-specific levels are configured,
        // check if this log level is allowed for this category
        // This allows fine-grained control (e.g., "debug" for MyClass, "info" for others)
        if ($this->levels !== [] && Level::gt($level, $this->getCategoryLevel($category))) {
            return;
        }

        // Create log entry with timestamp, hostname, and formatted time
        $log = new Log($level, $this->hostname ?? gethostname(), $this->time_format);
        $log->category = $category;
        // Set file and line location from the first trace entry (caller of log method)
        $log->setLocation($traces[0] ?? []);
        // Format message with placeholder interpolation and exception handling
        $log->message = $this->format($message, $context);

        // Dispatch event before writing to appenders
        // This allows listeners to inspect or modify the log entry
        $this->eventDispatcher->dispatch(new LoggerLog($this, $level, $message, $context, $log));

        // Write to all configured appenders (file, stdout, Redis, syslog, etc.)
        foreach ($this->appenders as $name) {
            $appender = $this->appenderFactory->get($name);
            $appender->append($log);
        }
    }
}
