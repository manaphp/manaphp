<?php

namespace ManaPHP\Logger\Appender;

use ManaPHP\Component;
use ManaPHP\Logger\AppenderInterface;

class FileContext
{
    /**
     * @var array
     */
    public $logs = [];
}

/**
 * Class ManaPHP\Logger\Appender\File
 *
 * @package logger
 */
class File extends Component implements AppenderInterface
{
    /**
     * @var string
     */
    protected $_file = '@data/logger/app.log';

    /**
     * @var string
     */
    protected $_format = '[:date][:client_ip][:request_id16][:level][:category][:location] :message';

    /**
     * @var array
     */
    protected $_lazy = false;

    /**
     * \ManaPHP\Logger\Adapter\File constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        $this->_context = new FileContext();

        if (is_string($options)) {
            $options = [strpos($options, ':') === false ? 'file' : 'format' => $options];
        }

        if (isset($options['file'])) {
            $this->_file = $options['file'];
        }

        if (isset($options['format'])) {
            $this->_format = $options['format'];
        }

        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            if (!isset($options['lazy']) || $options['lazy']) {
                $this->_lazy = true;
                $this->eventsManager->attachEvent('app:endRequest', [$this, '_writeLazyLog']);
            }
        }
    }

    public function _writeLazyLog()
    {
        $context = $this->_context;

        if ($context->logs) {
            $this->_write(implode($context->logs, ''));
            $context->logs = [];
        }
    }

    /**
     * @param \ManaPHP\Logger\Log $log
     *
     * @return string
     */
    protected function _format($log)
    {
        $replaced = [];

        $replaced[':date'] = date('Y-m-d\TH:i:s', $log->timestamp) . sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
        $replaced[':client_ip'] = $log->client_ip ?: '-';
        $replaced[':request_id'] = $log->request_id ?: '-';
        $replaced[':request_id16'] = $log->request_id ? substr($log->request_id, 0, 16) : '-';
        $replaced[':category'] = $log->category;
        $replaced[':location'] = "$log->file:$log->line";
        $replaced[':level'] = strtoupper($log->level);
        if ($log->category === 'exception') {
            $replaced[':message'] = '';
            /** @noinspection SuspiciousAssignmentsInspection */
            $replaced[':message'] = preg_replace('#[\\r\\n]+#', '\0' . strtr($this->_format, $replaced), $log->message) . PHP_EOL;
        } else {
            $replaced[':message'] = $log->message . PHP_EOL;
        }

        return strtr($this->_format, $replaced);
    }

    /**
     * @param string $str
     *
     * @return void
     */
    protected function _write($str)
    {
        $file = $this->alias->resolve($this->_file);
        if (!is_file($file)) {
            $dir = dirname($file);
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                /** @noinspection ForgottenDebugOutputInspection */
                trigger_error("Unable to create $dir directory: " . error_get_last()['message'], E_USER_WARNING);
            }
        }

        if (file_put_contents($file, $str, FILE_APPEND | LOCK_EX) === false) {
            /** @noinspection ForgottenDebugOutputInspection */
            trigger_error('Write log to file failed: ' . $file, E_USER_WARNING);
        }
    }

    /**
     * @param \ManaPHP\Logger\Log $log
     *
     * @return void
     */
    public function append($log)
    {
        if ($this->_lazy) {
            $context = $this->_context;

            $context->logs[] = $this->_format($log);
        } else {
            $this->_write($this->_format($log));
        }
    }

    public function __destruct()
    {
        if ($this->_lazy) {
            $this->_writeLazyLog();
        }
    }
}