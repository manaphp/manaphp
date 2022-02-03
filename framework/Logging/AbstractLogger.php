<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use JsonSerializable;
use ManaPHP\Component;
use ManaPHP\Coroutine;
use ManaPHP\Logging\Logger\Log;
use ManaPHP\Logging\Logger\LogCategorizable;
use Throwable;
use ArrayObject;

/**
 * @property-read \ManaPHP\AliasInterface                $alias
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Logging\AbstractLoggerContext $context
 */
abstract class AbstractLogger extends Component implements LoggerInterface
{
    protected string $level;
    protected string $hostname;

    protected bool $lazy;
    protected int $buffer_size = 1024;
    protected ?float $last_write = null;

    /**
     * @var \ManaPHP\Logging\Logger\Log[]
     */
    protected array $logs = [];

    public function __construct(array $options = [])
    {
        if (isset($options['level'])) {
            $this->level = $options['level'];
        } else {
            $error_level = error_reporting();

            if ($error_level & E_ERROR) {
                $this->level = Level::ERROR;
            } elseif ($error_level & E_WARNING) {
                $this->level = Level::WARNING;
            } elseif ($error_level & E_NOTICE) {
                $this->level = Level::NOTICE;
            } else {
                $this->level = Level::DEBUG;
            }
        }

        $this->lazy = defined('MANAPHP_CLI') ? false : $options['lazy'] ?? true;

        if (isset($options['buffer_size'])) {
            $this->buffer_size = (int)$options['buffer_size'];
        }

        $this->hostname = $options['hostname'] ?? gethostname();

        $this->attachEvent('request:end', [$this, 'onRequestEnd']);
    }

    protected function createContext(): AbstractLoggerContext
    {
        /** @var \ManaPHP\Logging\AbstractLoggerContext $context */
        $context = parent::createContext();

        $context->level = $this->level;
        $context->client_ip = defined('MANAPHP_CLI') ? '' : $this->request->getClientIp();
        $context->request_id = $this->request->getRequestId();

        return $context;
    }

    public function onRequestEnd(): void
    {
        if ($this->logs) {
            $this->append($this->logs);
            $this->logs = [];
        }
    }

    public function setLevel(string $level): static
    {
        $this->context->level = $level;

        return $this;
    }

    public function getLevel(): string
    {
        return $this->context->level;
    }

    public function setLazy(bool $lazy = true): static
    {
        $this->lazy = $lazy;

        return $this;
    }

    /**
     * @param \ManaPHP\Logging\Logger\Log[] $logs
     *
     * @return void
     */
    abstract public function append(array $logs): void;

    protected function getLocation(array $traces): array
    {
        for ($i = count($traces) - 1; $i >= 0; $i--) {
            $trace = $traces[$i];
            $function = $trace['function'];

            if (isset(Level::map()[$function])) {
                return $trace;
            } elseif (str_starts_with($function, 'log_') && isset(Level::map()[substr($function, 4)])) {
                return $trace;
            }
        }

        return [];
    }

    protected function inferCategory(array $traces): string
    {
        foreach ($traces as $trace) {
            if (isset($trace['object'])) {
                $object = $trace['object'];
                if ($object instanceof LogCategorizable) {
                    return $object->categorizeLog();
                }
            }
        }
        return 'unknown';
    }

    public function exceptionToString(Throwable $exception): string
    {
        $str = get_class($exception) . ': ' . $exception->getMessage() . PHP_EOL;
        $str .= '    at ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
        $traces = $exception->getTraceAsString();
        $str .= preg_replace('/#\d+\s/', '    at ', $traces);

        $prev = $traces;
        $caused = $exception;
        while ($caused = $caused->getPrevious()) {
            $str .= PHP_EOL . '  Caused by ' . get_class($caused) . ': ' . $caused->getMessage() . PHP_EOL;
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
        if ($message instanceof Throwable) {
            return $this->exceptionToString($message);
        } elseif ($message instanceof JsonSerializable || $message instanceof ArrayObject) {
            return json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        } elseif (!is_array($message)) {
            return (string)$message;
        }

        if (!isset($message[0]) || !is_string($message[0])) {
            return json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }

        if (substr_count($message[0], '%') + 1 >= ($count = count($message)) && isset($message[$count - 1])) {
            foreach ($message as $k => $v) {
                if ($k === 0 || is_scalar($v) || $v === null) {
                    continue;
                }

                if ($v instanceof Throwable) {
                    $message[$k] = $this->exceptionToString($v);
                } elseif (is_array($v)) {
                    $message[$k] = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
                } elseif ($v instanceof JsonSerializable || $v instanceof ArrayObject) {
                    $message[$k] = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
                }
            }
            return sprintf(...$message);
        }

        if (count($message) === 2) {
            if (isset($message[1]) && !str_contains($message[0], ':1')) {
                $message[0] = rtrim($message[0], ': ') . ': :1';
            }
        } elseif (count($message) === 3) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (isset($message[1], $message[2]) && !str_contains($message[0], ':1') && is_scalar($message[1])) {
                $message[0] = rtrim($message[0], ': ') . ': :1 => :2';
            }
        }

        $replaces = [];
        foreach ($message as $k => $v) {
            if ($k === 0) {
                continue;
            }

            if ($v instanceof Throwable) {
                $v = $this->exceptionToString($v);
            } elseif (is_array($v)) {
                $v = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
            } elseif ($v instanceof JsonSerializable) {
                $v = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
            } elseif (is_string($v)) {
                null;
            } elseif ($v === null || is_scalar($v)) {
                $v = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
            } else {
                $v = (string)$v;
            }

            $replaces[":$k"] = $v;
        }

        return strtr($message[0], $replaces);
    }

    public function log(string $level, mixed $message, ?string $category = null): static
    {
        $context = $this->context;
        $levels = Level::map();
        if ($levels[$level] > $levels[$context->level]) {
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

        $log->hostname = $this->hostname;
        $log->client_ip = $context->client_ip;
        $log->level = $level;
        $log->request_id = $context->request_id ?: $this->request->getRequestId();

        if ($message instanceof Throwable) {
            $log->category = $category ?: 'exception';
            $log->file = basename($message->getFile());
            $log->line = $message->getLine();
        } else {
            $traces = Coroutine::getBacktrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);
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

        $log->message = is_string($message) ? $message : $this->formatMessage($message);
        $log->timestamp = microtime(true);

        $this->fireEvent('logger:log', compact('level', 'message', 'category', 'log'));

        if ($this->lazy) {
            $this->logs[] = $log;

            if ($this->last_write === null) {
                $this->last_write = $log->timestamp;
            } elseif ($log->timestamp - $this->last_write > 1 || count($this->logs) > $this->buffer_size) {
                $this->last_write = $log->timestamp;

                $this->append($this->logs);
                $this->logs = [];
            }
        } else {
            $this->append([$log]);
        }

        return $this;
    }

    public function debug(mixed $message, ?string $category = null): static
    {
        return $this->log(Level::DEBUG, $message, $category);
    }

    public function info(mixed $message, ?string $category = null): static
    {
        return $this->log(Level::INFO, $message, $category);
    }

    public function notice(mixed $message, ?string $category = null): static
    {
        return $this->log(Level::NOTICE, $message, $category);
    }

    public function warning(mixed $message, ?string $category = null): static
    {
        return $this->log(Level::WARNING, $message, $category);
    }

    public function error(mixed $message, ?string $category = null): static
    {
        return $this->log(Level::ERROR, $message, $category);
    }

    public function critical(mixed $message, ?string $category = null): static
    {
        return $this->log(Level::CRITICAL, $message, $category);
    }

    public function alert(mixed $message, ?string $category = null): static
    {
        return $this->log(Level::ALERT, $message, $category);
    }

    public function emergency(mixed $message, ?string $category = null): static
    {
        return $this->log(Level::EMERGENCY, $message, $category);
    }

    public function dump(): array
    {
        $data = parent::dump();

        unset($data['logs'], $data['last_write']);

        return $data;
    }
}