<?php

namespace ManaPHP;

use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Logger\Log;
use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Logger
 *
 * @package logger
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Logger extends Component implements LoggerInterface
{
    const LEVEL_FATAL = 10;
    const LEVEL_ERROR = 20;
    const LEVEL_WARN = 30;
    const LEVEL_INFO = 40;
    const LEVEL_DEBUG = 50;

    /**
     * @var int
     */
    protected $_level;

    /**
     * @var array
     */
    protected $_appenders = [];

    /**
     * @var array
     */
    protected static $_levels = [
        self::LEVEL_FATAL => 'fatal',
        self::LEVEL_ERROR => 'error',
        self::LEVEL_WARN => 'warn',
        self::LEVEL_INFO => 'info',
        self::LEVEL_DEBUG => 'debug'];

    /**
     * Logger constructor.
     *
     * @param string|array|\ManaPHP\Logger\AppenderInterface $options
     *
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $this->_appenders[($pos = strrpos($options, '\\')) !== false ? lcfirst(substr($options, $pos + 1)) : $options] = $options;
        } elseif (is_object($options)) {
            $this->_appenders[] = ['appender' => ['instance' => $options]];
        } elseif ($options) {
            if (isset($options['level'])) {
                $this->setLevel($options['level']);
                unset($options['level']);
            }

            if ($options) {
                $appenders = isset($options['appenders']) ? $options['appenders'] : $options;
                foreach ($appenders as $k => $v) {
                    $this->_appenders[is_string($k) ? $k : $v] = $v;
                }
            } else {
                $this->_appenders['file'] = 'ManaPHP\Logger\Appender\File';
            }
        } else {
            $this->_appenders['file'] = 'ManaPHP\Logger\Appender\File';
        }

        if ($this->_level === null) {
            $error_level = error_reporting();

            if ($error_level & E_ERROR) {
                $this->_level = self::LEVEL_ERROR;
            } elseif ($error_level & E_WARNING) {
                $this->_level = self::LEVEL_WARN;
            } elseif ($error_level & E_NOTICE) {
                $this->_level = self::LEVEL_INFO;
            } else {
                $this->_level = self::LEVEL_DEBUG;
            }
        }
    }

    /**
     * @param int|string $level
     *
     * @return static
     */
    public function setLevel($level)
    {
        if (is_numeric($level)) {
            $this->_level = (int)$level;
        } else {
            $this->_level = array_search($level, self::$_levels, true);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
     * @return array
     */
    public function getLevels()
    {
        return self::$_levels;
    }

    /**
     * @param array $traces
     *
     * @return array
     */
    protected function _getLocation($traces)
    {
        for ($i = count($traces) - 1; $i >= 0; $i--) {
            $trace = $traces[$i];
            if ($trace['function'] === '__call' || in_array($trace['function'], self::$_levels, true)) {
                return $trace;
            }
        }

        return [];
    }

    /**
     * @param array $traces
     *
     * @return string
     */
    protected function _inferCategory($traces)
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

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAppender($name)
    {
        return isset($this->_appenders[$name]);
    }

    /**
     * @param int|string $name
     *
     * @return \ManaPHP\Logger\AppenderInterface|false
     */
    public function getAppender($name)
    {
        if (!isset($this->_appenders[$name])) {
            return false;
        }

        $appender = $this->_appenders[$name];
        if (is_object($appender)) {
            return $appender;
        } elseif (is_string($appender)) {
            $className = strpos($appender, '\\') !== false ? $appender : 'ManaPHP\Logger\Appender\\' . ucfirst($appender);
            return $this->_appenders[$name] = $this->_di->getShared($className);
        } elseif (is_array($appender)) {
            if (isset($appender['instance'])) {
                return $appender['instance'];
            } else {
                if (isset($appender['level'])) {
                    $appender['level'] = array_search($appender['level'], self::$_levels, true);
                }
                $className = isset($appender['class']) ? $appender['class'] : $name;
                $className = strpos($className, '\\') ? $className : 'ManaPHP\Logger\Appender\\' . ucfirst($className);
                $appender['instance'] = $instance = $this->_di->getInstance($className, $appender);
                $this->_appenders[$name] = $appender;
                return $instance;
            }
        } else {
            throw new MisuseException('');
        }
    }

    /**
     * @param string|array|\ManaPHP\Logger\AppenderInterface $appender
     * @param string                                         $name
     *
     * @return static
     */
    public function addAppender($appender, $name = null)
    {
        $level = null;
        if (is_string($appender)) {
            $definition = $appender;
        } elseif (isset($appender['level'])) {
            $level = is_numeric($appender['level']) ? (int)$appender['level'] : array_search(strtolower($appender['level']), self::$_levels, true);
            unset($appender['level']);
            $definition = $appender;
        } else {
            $definition = $appender;
        }

        if (is_string($definition)) {
            if (strpos($definition, '\\') === false) {
                $definition = 'ManaPHP\Logger\Appender\\' . ucfirst($definition);
            }
        } elseif (is_array($definition) && !isset($definition[0]) && !isset($definition['class'])) {
            throw new InvalidArgumentException($appender);
        } elseif (isset($definition['class']) && strpos($definition['class'], '\\') === false) {
            $definition['class'] = 'ManaPHP\Logger\Appender\\' . ucfirst($definition['class']);
        }

        if ($name === null) {
            $this->_appenders[] = $level !== null ? ['level' => $level, 'appender' => $definition] : ['appender' => $definition];
        } else {
            $this->_appenders[$name] = $level !== null ? ['level' => $level, 'appender' => $definition] : ['appender' => $definition];
        }

        return $this;
    }

    /**
     * @param string $appender
     *
     * @return static
     */
    public function removeAppender($appender)
    {
        unset($this->_appenders[$appender]);

        return $this;
    }

    /**
     * @param \Exception $exception
     *
     * @return string
     */
    public function exceptionToString($exception)
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
        if ($app = $this->alias->get('@root')) {
            $replaces[dirname(realpath($this->alias->resolve('@root'))) . DIRECTORY_SEPARATOR] = '';
        }

        return strtr($str, $replaces);
    }

    /**
     * @param \Exception|array|\Serializable|\JsonSerializable $message
     *
     * @return string
     */
    public function formatMessage($message)
    {
        if ($message instanceof \Exception || (interface_exists('\Throwable') && $message instanceof \Throwable)) {
            return $this->exceptionToString($message);
        } elseif ($message instanceof \JsonSerializable) {
            return json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif ($message instanceof \Serializable) {
            return serialize($message);
        } elseif (!is_array($message)) {
            return (string)$message;
        }

        if (!isset($message[0]) || !is_string($message[0])) {
            return json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (substr_count($message[0], '%') + 1 >= ($count = count($message)) && isset($message[$count - 1])) {
            foreach ((array)$message as $k => $v) {
                if ($k === 0 || is_scalar($v) || $v === null) {
                    continue;
                }

                if ($v instanceof \Exception || (interface_exists('\Throwable') && $v instanceof \Throwable)) {
                    $message[$k] = $this->exceptionToString($v);
                } elseif (is_array($v) || $v instanceof \JsonSerializable) {
                    $message[$k] = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            /** @noinspection ArgumentUnpackingCanBeUsedInspection */
            return (string)call_user_func_array('sprintf', $message);
        }

        if (count($message) === 2) {
            if (isset($message[1]) && strpos($message[0], ':1') === false) {
                $message[0] = rtrim($message[0], ': ') . ': :1';
            }
        } elseif (count($message) === 3) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (isset($message[1], $message[2]) && strpos($message[0], ':1') === false && is_scalar($message[1])) {
                $message[0] = rtrim($message[0], ': ') . ': :1 => :2';
            }
        }

        $replaces = [];
        /** @noinspection ForeachSourceInspection */
        foreach ($message as $k => $v) {
            if ($k === 0) {
                continue;
            }

            if ($v instanceof \Exception || (interface_exists('\Throwable') && $v instanceof \Throwable)) {
                $v = $this->exceptionToString($v);
            } elseif (is_array($v) || $v instanceof \JsonSerializable) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif ($v instanceof \Serializable) {
                $v = serialize($v);
            } elseif (is_string($v)) {
                null;
            } elseif ($v === null || is_scalar($v)) {
                $v = json_encode($v);
            } else {
                $v = (string)$v;
            }

            $replaces[":$k"] = $v;
        }

        return strtr($message[0], $replaces);
    }

    /**
     * @param int          $level
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function log($level, $message, $category = null)
    {
        if ($level > $this->_level) {
            return $this;
        }

        if ($category !== null && !is_string($category)) {
            $message = [$message . ': :param', 'param' => $category];
            $category = null;
        }

        if (is_array($message) && count($message) === 1 && isset($message[0])) {
            $message = $message[0];
        }

        $request_id = empty($_SERVER['DOCUMENT_ROOT']) ? '' : $this->request->getServer('HTTP_X_REQUEST_ID');

        $log = new Log();

        $log->host = gethostname();
        $log->client_ip = empty($_SERVER['DOCUMENT_ROOT']) ? '' : $this->request->getClientIp();
        $log->level = self::$_levels[$level];
        $log->request_id = $request_id ? preg_replace('#[^a-zA-Z\d-_\.]#', 'X', $request_id) : '';

        if ($message instanceof \Exception) {
            $log->category = $category ?: 'exception';
            $log->file = basename($message->getFile());
            $log->line = $message->getLine();
        } else {
            if (MANAPHP_COROUTINE) {
                /** @noinspection PhpUndefinedMethodInspection */
                $traces = \Swoole\Coroutine::getBackTrace(0, DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);
            } else {
                $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);
            }
            /** @noinspection NestedTernaryOperatorInspection */
            $log->category = $category ?: $this->_inferCategory($traces);
            $location = $this->_getLocation($traces);
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

        if ($this->eventsManager->fireEvent('logger:log', $this, $log) === false) {
            return $this;
        }

        /**
         * @var \ManaPHP\Logger\AppenderInterface $appender
         */
        foreach ($this->_appenders as $name => $value) {
            if (is_object($value)) {
                $appender = $value;
            } elseif (is_string($value)) {
                $appender = $this->getAppender($name);
            } elseif (is_array($value)) {
                if (isset($value['level']) && $level > $value['level']) {
                    continue;
                }
                $appender = isset($value['instance']) ? $value['instance'] : $this->getAppender($name);
            } else {
                throw new MisuseException('');
            }

            $appender->append($log);
        }

        return $this;
    }

    /**
     * Sends/Writes a debug message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function debug($message, $category = null)
    {
        return $this->log(self::LEVEL_DEBUG, $message, $category);
    }

    /**
     * Sends/Writes an info message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function info($message, $category = null)
    {
        return $this->log(self::LEVEL_INFO, $message, $category);
    }

    /**
     * Sends/Writes a warning message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function warn($message, $category = null)
    {
        return $this->log(self::LEVEL_WARN, $message, $category);
    }

    /**
     * Sends/Writes an error message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function error($message, $category = null)
    {
        return $this->log(self::LEVEL_ERROR, $message, $category);
    }

    /**
     * Sends/Writes a critical message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function fatal($message, $category = null)
    {
        return $this->log(self::LEVEL_FATAL, $message, $category);
    }
}