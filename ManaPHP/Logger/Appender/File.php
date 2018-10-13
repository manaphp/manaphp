<?php

namespace ManaPHP\Logger\Appender;

use ManaPHP\Component;
use ManaPHP\Logger\AppenderInterface;

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
    protected $_format = '[:date][:client_ip][:request_id16][:level][:process_id][:category][:location] :message';

    /**
     * \ManaPHP\Logger\Adapter\File constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['file' => $options];
        }

        if (isset($options['file'])) {
            $this->_file = $options['file'];
        }

        if (isset($options['format'])) {
            $this->_format = $options['format'];
        }
    }

    /**
     * @param \ManaPHP\Logger\Log $log
     *
     * @return void
     */
    public function append($log)
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

        $replaced = [];

        $replaced[':date'] = date('Y-m-d\TH:i:s', $log->timestamp) . sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
        $replaced[':client_ip'] = $log->client_ip ?: '-';
        $replaced[':request_id'] = $log->request_id ?: '-';
        $replaced[':request_id16'] = $log->request_id ? substr($log->request_id, 0, 16) : '-';
        $replaced[':process_id'] = $log->process_id;
        $replaced[':category'] = $log->category;
        $replaced[':location'] = $log->location;
        $replaced[':level'] = strtoupper($log->level);
        $replaced[':message'] = $log->message . PHP_EOL;

        if (file_put_contents($file, strtr($this->_format, $replaced), FILE_APPEND | LOCK_EX) === false) {
            /** @noinspection ForgottenDebugOutputInspection */
            trigger_error('Write log to file failed: ' . $file, E_USER_WARNING);
        }
    }
}