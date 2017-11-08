<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2016/3/20
 */
namespace ManaPHP;

use ManaPHP\Logger\Exception as LoggerException;

/**
 * Class ManaPHP\Logger
 *
 * @package logger
 */
class Logger extends Component implements LoggerInterface
{
    const LEVEL_FATAL = 'FATAL';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_WARN = 'WARN';
    const LEVEL_INFO = 'INFO';
    const LEVEL_DEBUG = 'DEBUG';

    /**
     * @var array
     */
    protected $_s2i;

    /**
     * @var string
     */
    protected $_level = self::LEVEL_DEBUG;

    /**
     * @var string
     */
    protected $_category;

    /**
     * @var \ManaPHP\Logger\AdapterInterface
     */
    public $adapter;

    /**
     * Logger constructor.
     *
     * @param string|array|\ManaPHP\Logger\AdapterInterface $options
     *
     * @throws \ManaPHP\Logger\Exception
     */
    public function __construct($options = [])
    {
        if (is_object($options) || is_string($options)) {
            $options = ['adapter' => $options];
        }

        if (isset($options['adapter'])) {
            $this->adapter = $options['adapter'];
        }

        if (isset($options['level'])) {
            $this->setLevel($options['level']);
        }

        $this->_s2i = array_flip([self::LEVEL_FATAL, self::LEVEL_ERROR, self::LEVEL_WARN, self::LEVEL_INFO, self::LEVEL_DEBUG]);
    }

    /**
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        if (!is_object($this->adapter)) {
            $this->adapter = $this->_dependencyInjector->getShared($this->adapter);
        }

        return $this;
    }

    /**
     * Filters the logs sent to the handlers to be greater or equals than a specific level
     *
     * @param string $level
     *
     * @return static
     * @throws \ManaPHP\Logger\Exception
     */
    public function setLevel($level)
    {
        if (!isset($this->_s2i[$level])) {
            throw new LoggerException('`:level` level is not one of `:levels`'/**m0511c3e8c2bcd64c8*/,
                ['level' => $level, 'levels' => implode(',', array_keys($this->getLevels()))]);
        }

        $this->_level = $level;

        return $this;
    }

    /**
     * Returns the current log level
     *
     * @return string
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
        return $this->_s2i;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->_category = $category;
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
        }

        if (isset($trace['class'], $trace['type'], $trace['function'])) {
            return $trace['class'] . $trace['type'] . $trace['function'];
        }
        return '';
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
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

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

        $context = [];

        $context['level'] = $level;
        $context['message'] = $message;
        $context['category'] = $category ?: $this->_category;
        $context['timestamp'] = time();
        $context['location'] = $this->_getLocation($traces);
        $context['caller'] = $this->_getCaller($traces);

        $this->fireEvent('logger:log', $context);

        if ($this->_s2i[$level] > $this->_s2i[$this->_level]) {
            return $this;
        }

        $this->adapter->log($level, $message, $context);

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