<?php

namespace ManaPHP;

use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Logger\Log;
use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Logger
 *
 * @package logger
 * @property \ManaPHP\Http\RequestInterface   $request
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 */
class Logger extends Component implements LoggerInterface
{
    const LEVEL_FATAL = 10;
    const LEVEL_ERROR = 20;
    const LEVEL_WARN = 30;
    const LEVEL_INFO = 40;
    const LEVEL_DEBUG = 50;

    /**
     * @var string
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
            $this->_appenders[] = ['appender' => $options];
        } elseif (is_object($options)) {
            $this->_appenders[] = ['appender' => ['instance' => $options]];
        } else {
            if (isset($options['level']) && $options['level']) {
                $this->setLevel($options['level']);
            }

            if (isset($options['category']) && $options['category']) {
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
                    $level = is_numeric($value['level']) ? (int)$value['level'] : array_search(strtolower($value['level']), $this->getConstants('level'), true);
                    unset($value['level']);
                    $appender = $value;
                } else {
                    $appender = $value;
                }

                if (is_array($appender) && !isset($appender[0]) && !isset($appender['class'])) {
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

                $this->_appenders[] = $level !== null ? ['level' => $level, 'appender' => $appender] : ['appender' => $appender];
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
            $this->_level = array_flip($this->getConstants('level'))[strtolower($level)];
        }

        return $this;
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
        return $this->getConstants('level');
    }

    /**
     * @param array $traces
     *
     * @return string
     */
    protected function _getLocation($traces)
    {
        if (isset($traces[2]['function']) && !isset($this->_levels[strtoupper($traces[2]['function'])])) {
            $trace = $traces[1];
        } else {
            $trace = $traces[2];
        }

        if (isset($trace['file'], $trace['line'])) {
            return str_replace($this->alias->get('@app'), '', strtr($trace['file'], '\\', '/')) . ':' . $trace['line'];
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
        return '';
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
     * @param string       $level
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

        if (is_array($message)) {
            $replaces = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($message as $k => $v) {
                if ($k !== 0) {
                    $replaces[":$k"] = $v;
                }
            }

            $message = strtr($message[0], $replaces);
        }

        $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);

        $log = new Log();
        $log->level = $this->_levels[$level];
        $log->category = $category ?: ($this->_category ?: $this->_inferCategory($traces));
        $log->location = $this->_getLocation($traces);
        $log->message = $message;
        $log->timestamp = time();

        $this->fireEvent('logger:log', $log);

        /**
         * @var \ManaPHP\Logger\AppenderInterface $appender
         */
        foreach ($this->_appenders as $name => $value) {
            if (isset($value['level']) && $level > $value['level']) {
                continue;
            }

            if (!isset($value['instance'])) {
                $appender = $this->_appenders[$name]['instance'] = $this->_di->getInstance($value['appender']);
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