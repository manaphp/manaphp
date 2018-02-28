<?php

namespace ManaPHP;

/**
 * Class ManaPHP\Logger
 *
 * @package logger
 * @property \ManaPHP\Http\RequestInterface   $request
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 */
class Logger extends Component implements LoggerInterface
{
    const LEVEL_FATAL = 'FATAL';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_WARN = 'WARN';
    const LEVEL_INFO = 'INFO';
    const LEVEL_DEBUG = 'DEBUG';

    /**
     * @var string
     */
    protected $_level = 'DEBUG';

    /**
     * @var array
     */
    protected $_s2i;

    /**
     * @var array
     */
    protected $_appenders;

    /**
     * @var string
     */
    protected $_defaultCategory = '';

    /**
     * @var string
     */
    protected $_prefix = 'app';

    /**
     * Logger constructor.
     *
     * @param string|array|\ManaPHP\Logger\AppenderInterface $options
     *
     */
    public function __construct($options = 'ManaPHP\Logger\Appender\File')
    {
        $this->_s2i = array_flip([self::LEVEL_FATAL, self::LEVEL_ERROR, self::LEVEL_WARN, self::LEVEL_INFO, self::LEVEL_DEBUG]);

        if (is_string($options) || is_object($options)) {
            $options = ['appenders' => [['class' => $options]]];
        }

        if (isset($options['appenders'])) {
            foreach ((array)$options['appenders'] as $name => $appender) {
                if (isset($appender['filter'])) {
                    $filter = $appender['filter'];
                    if (isset($filter['level'])) {
                        $filter['level'] = strtoupper($filter['level']);
                    }
                    unset($appender['filter']);
                } else {
                    $filter = [];
                }

                $this->_appenders[$name] = ['filter' => $filter, 'appender' => $appender];
            }
        }

        if (isset($options['level'])) {
            $this->_level = strtoupper($options['level']);
        }

        $mca = $this->dispatcher->getMCA('.');

        if (trim($mca, '.') === 0) {
            $this->_defaultCategory = $this->_prefix;
        } else {
            $this->_defaultCategory = $this->_prefix . '.' . $mca;
        }

        $this->attachEvent('dispatcher:beforeDispatch', function ($source) {
            /**
             * @var \ManaPHP\Mvc\DispatcherInterface $source
             */
            $this->_defaultCategory = $this->_prefix . '.' . $source->getMCA('.');
        });
    }

    /**
     * @param string $category
     */
    public function setDefaultCategory($category)
    {
        $this->_defaultCategory = $this->_prefix . $category;
    }

    /**
     * @return array
     */
    public function getLevels()
    {
        return $this->_s2i;
    }

    /**
     * @param array $traces
     *
     * @return string
     */
    protected function _getLocation($traces)
    {
        if (isset($traces[2]['function']) && !isset($this->_s2i[strtoupper($traces[2]['function'])])) {
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
     * @param array[] $traces
     *
     * @return string
     */
    protected function _getCaller($traces)
    {
        if (isset($traces[2]['function']) && !isset($this->_s2i[strtoupper($traces[2]['function'])])) {
            $trace = $traces[2];
        } elseif (isset($traces[3])) {
            $trace = $traces[3];
        } else {
            return '';
        }

        if (isset($trace['class'], $trace['type'], $trace['function'])) {
            return $trace['class'] . $trace['type'] . $trace['function'];
        }

        return '';
    }

    /**
     * @param array $filter
     * @param array $logEvent
     *
     * @return bool
     */
    protected function _IsFiltered($filter, $logEvent)
    {
        if (isset($filter['level']) && $this->_s2i[$filter['level']] < $this->_s2i[$logEvent['level']]) {
            return true;
        }

        foreach ($filter as $field => $definition) {
            if ($field === 'level') {
                continue;
            }

            $matchNothing = true;
            foreach (explode(',', $definition) as $pattern) {
                $value = $logEvent[$field];
                if ($value === $pattern || fnmatch($pattern, $value)) {
                    $matchNothing = false;
                    break;
                }
            }

            if ($matchNothing) {
                return true;
            }
        }

        return false;
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
        if ($this->_level !== $level && $this->_s2i[$this->_level] < $this->_s2i[$level]) {
            return $this;
        }

        if (is_array($message)) {
            $replaces = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($message as $k => $v) {
                if ($k !== 0) {
                    $replaces['{' . $k . '}'] = $v;
                }
            }

            $message = strtr($message[0], $replaces);
        }

        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        $logEvent = [];

        $logEvent['level'] = $level;
        $logEvent['category'] = $category ?: $this->_defaultCategory;
        $logEvent['location'] = $this->_getLocation($traces);
        $logEvent['message'] = $message;
        $logEvent['caller'] = $this->_getCaller($traces);
        $logEvent['client_ip'] = $this->request->getClientAddress();
        $logEvent['timestamp'] = time();

        $this->fireEvent('logger:log', $logEvent);

        /**
         * @var \ManaPHP\Logger\AppenderInterface $appender
         */
        foreach ($this->_appenders as $name => $appender_conf) {
            if (!$this->_IsFiltered($appender_conf['filter'], $logEvent)) {
                if (!isset($appender_conf['instance'])) {
                    $appender = $this->_appenders[$name]['instance'] = $this->_dependencyInjector->getInstance($appender_conf['appender']);
                } else {
                    $appender = $appender_conf['instance'];
                }

                $appender->append($logEvent);
            }
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