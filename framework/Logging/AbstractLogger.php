<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Component;
use ManaPHP\Context\ContextCreatorInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Coroutine;
use ManaPHP\Event\EventTrait;
use ManaPHP\Logging\Logger\Log;
use ManaPHP\Logging\Logger\LogCategorizable;
use Throwable;

/**
 * @property-read \ManaPHP\AliasInterface                $alias
 * @property-read \ManaPHP\Http\RequestInterface         $request
 */
abstract class AbstractLogger extends Component implements LoggerInterface, ContextCreatorInterface
{
    use EventTrait;
    use ContextTrait;

    protected string $level;
    protected string $hostname;

    public function __construct(string $level = Level::DEBUG, ?string $hostname = null)
    {
        $this->level = $level;
        $this->hostname = $hostname ?? gethostname();
    }

    public function createContext(): AbstractLoggerContext
    {
        /** @var AbstractLoggerContext $context */
        $context = $this->contextor->makeContext($this);

        $context->client_ip = defined('MANAPHP_CLI') ? '' : $this->request->getClientIp();
        $context->request_id = $this->request->getRequestId();

        return $context;
    }

    abstract public function append(Log $log): void;

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
        /** @var AbstractLoggerContext $context */
        $context = $this->getContext();
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

        $this->append($log);

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