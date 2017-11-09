<?php

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
     * @var array
     */
    protected $_adapters;

    /**
     * @var string
     */
    protected $_category;

    /**
     * Logger constructor.
     *
     * @param string|array|\ManaPHP\Logger\AdapterInterface $options
     *
     * @throws \ManaPHP\Logger\Exception
     */
    public function __construct($options = [])
    {
        $this->_s2i = array_flip([self::LEVEL_FATAL, self::LEVEL_ERROR, self::LEVEL_WARN, self::LEVEL_INFO, self::LEVEL_DEBUG]);

        if (is_string($options)) {
            $options = ['adapters' => [[['class' => $options]]]];
        }

        if (isset($options['adapters'])) {
            foreach ($options['adapters'] as $adapter) {
                $this->_addAdapter($adapter);
            }
        }
    }

    /**
     * @param array $options
     */
    protected function _addAdapter($options)
    {
        $adapterOptions = isset($options[0]) ? $options[0] : $options['adapter'];

        $className = $adapterOptions['class'];
        $parameters = $adapterOptions;
        unset($parameters['class']);
        $adapter['adapter'] = Di::getDefault()->getShared($className, $parameters);

        if (isset($options['level'])) {
            $level = strtoupper($options['level']);
            if (!isset($this->_s2i[$level])) {
                throw new LoggerException('`:level` level is invalid', ['level' => $options['level']]);
            } else {
                $adapter['level'] = $this->_s2i[$level];
            }
        }

        if (isset($options['categories'])) {
            $adapter['categories'] = (array)$options['categories'];
        }

        $this->_adapters[] = $adapter;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->_category = $category;
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

        foreach ($this->_adapters as $adapter) {
            if (isset($adapter['level']) && $adapter['level'] < $this->_s2i[$level]) {
                continue;
            }

            if (isset($adapter['categories'])) {
                foreach ($adapter['categories'] as $cat) {
                    if (fnmatch($cat, $context['category'])) {
                        $adapter['adapter']->log($level, $message, $context);
                        break;
                    }
                }
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