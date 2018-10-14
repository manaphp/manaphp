<?php

namespace ManaPHP;

use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Logger\Log;
use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Logger
 *
 * @package logger
 * @property-read \ManaPHP\Http\RequestInterface   $request
 * @property-read \ManaPHP\Mvc\DispatcherInterface $dispatcher
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
     * @var string
     */
    protected $_category;

    /**
     * @var array
     */
    protected $_appenders = [];

    /**
     * @var array
     */
    protected $_levels = [];

    /**
     * Logger constructor.
     *
     * @param string|array|\ManaPHP\Logger\AppenderInterface $options
     *
     */
    public function __construct($options = 'ManaPHP\Logger\Appender\File')
    {
        $this->_levels = $this->getConstants('level');

        if (is_string($options)) {
            $this->_appenders[($pos = strrpos($options, '\\')) !== false ? lcfirst(substr($options, $pos + 1)) : $options] = ['appender' => $options];
        } elseif (is_object($options)) {
            $this->_appenders[] = ['appender' => ['instance' => $options]];
        } else {
            if (!empty($options['level'])) {
                $this->setLevel($options['level']);
            }

            if (!empty($options['category'])) {
                $this->_category = $options['category'];
            }

            unset($options['level'], $options['category']);

            if (isset($options['appenders'])) {
                $options = $options['appenders'];
            }

            foreach ((array)$options as $name => $value) {
                $level = null;
                if (is_int($name)) {
                    $appender = $value;
                } elseif (is_string($value)) {
                    $appender = $value;
                } elseif (isset($value['level'])) {
                    $level = is_numeric($value['level']) ? (int)$value['level'] : array_search(strtolower($value['level']), $this->_levels, true);
                    unset($value['level']);
                    $appender = $value;
                } else {
                    $appender = $value;
                }

                if (is_string($appender)) {
                    if (strpos($appender, '\\') === false) {
                        $appender = 'ManaPHP\Logger\Appender\\' . ucfirst($appender);
                    }
                } elseif (is_array($appender) && !isset($appender[0]) && !isset($appender['class'])) {
                    if (is_string($name)) {
                        $appenderClassName = 'ManaPHP\Logger\Appender\\' . ucfirst($name);
                        if (!class_exists($appenderClassName)) {
                            throw new InvalidArgumentException($value);
                        }
                        $appender[0] = $appenderClassName;
                    } else {
                        throw new InvalidArgumentException($value);
                    }
                }

                $this->_appenders[$name] = $level !== null ? ['level' => $level, 'appender' => $appender] : ['appender' => $appender];
            }
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
            $this->_level = array_search(strtolower($level), $this->_levels, true);
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
     * @param string $category
     *
     * @return static
     */
    public function setCategory($category)
    {
        $this->_category = $category;
        return $this;
    }

    /**
     * @return array
     */
    public function getLevels()
    {
        return $this->_levels;
    }

    /**
     * @param array $traces
     *
     * @return string
     */
    protected function _getLocation($traces)
    {
        for ($i = count($traces) - 1; $i >= 0; $i--) {
            $trace = $traces[$i];
            if ($trace['function'] === '__call' || in_array($trace['function'], $this->_levels, true)) {
                break;
            }
        }

        if (isset($trace['file'], $trace['line'])) {
            /** @noinspection PhpUndefinedVariableInspection */
            return basename($trace['file']) . ':' . $trace['line'];
        }

        return '';
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

        if (!isset($appender['instance'])) {
            return $this->_appenders[$name]['instance'] = $this->_di->getInstance($appender['appender']);
        } else {
            return $appender['instance'];
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
            $level = is_numeric($appender['level']) ? (int)$appender['level'] : array_search(strtolower($appender['level']), $this->_levels, true);
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

        $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);

        $log = new Log();

        $log->host = gethostname();
        $log->client_ip = isset($_SERVER['SERVER_ADDR']) ? $this->request->getClientIp() : '';
        $log->level = $this->_levels[$level];
        $log->request_id = isset($_SERVER['HTTP_X_REQUEST_ID']) ? preg_replace('#[^a-zA-Z\d-_\.]#', 'X', $_SERVER['HTTP_X_REQUEST_ID']) : '';
        /** @noinspection NestedTernaryOperatorInspection */
        $log->category = $category ?: ($this->_category ?: $this->_inferCategory($traces));
        $log->location = $this->_getLocation($traces);
        $log->message = is_string($message) ? $message : $this->formatMessage($message);
        $log->timestamp = microtime(true);

        if ($this->fireEvent('logger:log', $log) === false) {
            return $this;
        }

        /**
         * @var \ManaPHP\Logger\AppenderInterface $appender
         */
        foreach ($this->_appenders as $name => $value) {
            if (isset($value['level']) && $level > $value['level']) {
                continue;
            }

            if (!isset($value['instance'])) {
                $this->_appenders[$name]['instance'] = $this->_di->getInstance($value['appender']);
                $appender = $this->_appenders[$name]['instance'];
            } else {
                $appender = $value['instance'];
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
     * @param    string    $category
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