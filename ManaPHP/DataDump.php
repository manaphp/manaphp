<?php

namespace ManaPHP;

use JsonSerializable;
use Serializable;
use Swoole\Coroutine;
use Throwable;

class DataDump extends Component implements DataDumpInterface
{
    /**
     * @var string
     */
    protected $_format = '[:time][:location] :message';

    /**
     * DataDump constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['format'])) {
            $this->_format = $options['format'];
        }
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
            $function = $trace['function'];
            if ($function === 'output') {
                return $traces[$i + 1];
            }
        }

        return [];
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
            $replaces[dirname(realpath($this->alias->get('@root'))) . DIRECTORY_SEPARATOR] = '';
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
        if ($message instanceof Throwable) {
            return $this->exceptionToString($message);
        } elseif ($message instanceof JsonSerializable) {
            return json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        } elseif ($message instanceof Serializable) {
            return serialize($message);
        } elseif (!is_array($message)) {
            return (string)$message;
        }

        if (!isset($message[0]) || !is_string($message[0])) {
            return json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }

        if (substr_count($message[0], '%') + 1 >= ($count = count($message)) && isset($message[$count - 1])) {
            foreach ((array)$message as $k => $v) {
                if ($k === 0 || is_scalar($v) || $v === null) {
                    continue;
                }

                if ($v instanceof Throwable) {
                    $message[$k] = $this->exceptionToString($v);
                } elseif (is_array($v)) {
                    $message[$k] = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
                } elseif ($v instanceof JsonSerializable) {
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
            } elseif ($v instanceof Serializable) {
                $v = serialize($v);
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

    /**
     * @param mixed $message
     *
     * @return void
     */
    public function output($message)
    {
        if (is_array($message) && count($message) === 1 && isset($message[0])) {
            $message = $message[0];
        }

        if ($message instanceof Throwable) {
            $file = basename($message->getFile());
            $line = $message->getLine();
        } else {
            if (MANAPHP_COROUTINE_ENABLED) {
                /** @noinspection PhpUndefinedMethodInspection */
                $traces = Coroutine::getBackTrace(0, DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            } else {
                $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            }

            $location = $this->_getLocation($traces);
            if (isset($location['file'])) {
                $file = basename($location['file']);
                $line = $location['line'];
            } else {
                $file = '';
                $line = 0;
            }
        }

        $message = is_string($message) ? $message : $this->formatMessage($message);
        $timestamp = microtime(true);

        $replaced = [];

        $replaced[':time'] = date('H:i:s', $timestamp) . sprintf('.%03d', ($timestamp - (int)$timestamp) * 1000);
        $replaced[':date'] = date('Y-m-d\T', $timestamp) . $replaced[':time'];
        $replaced[':location'] = "$file:$line";
        $replaced[':message'] = $message;

        echo strtr($this->_format, $replaced), PHP_EOL;
    }
}