<?php

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\Logger;

class File extends Logger
{
    /**
     * @var string
     */
    protected $_file = '@data/logger/{id}.log';

    /**
     * @var string
     */
    protected $_format = '[:date][:client_ip][:request_id16][:level][:category][:location] :message';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($options['file'])) {
            $this->_file = $options['file'];
        }

        $this->_file = strtr($this->_file, ['{id}' => $this->configure->id]);

        if (isset($options['format'])) {
            $this->_format = $options['format'];
        }
    }

    /**
     * @param \ManaPHP\Logging\Logger\Log $log
     *
     * @return string
     */
    protected function format($log)
    {
        $replaced = [];

        $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
        $replaced[':date'] = date('Y-m-d\TH:i:s', $log->timestamp) . $ms;
        $replaced[':client_ip'] = $log->client_ip ?: '-';
        $replaced[':request_id'] = $log->request_id ?: '-';
        $replaced[':request_id16'] = $log->request_id ? substr($log->request_id, 0, 16) : '-';
        $replaced[':category'] = $log->category;
        $replaced[':location'] = "$log->file:$log->line";
        $replaced[':level'] = strtoupper($log->level);
        if ($log->category === 'exception') {
            $replaced[':message'] = '';
            $message = preg_replace('#[\\r\\n]+#', '\0' . strtr($this->_format, $replaced), $log->message);
            $replaced[':message'] = $message . PHP_EOL;
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
    protected function write($str)
    {
        $file = $this->alias->resolve($this->_file);
        if (!is_file($file)) {
            $dir = dirname($file);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                trigger_error("Unable to create $dir directory: " . error_get_last()['message'], E_USER_WARNING);
            }
        }

        //LOCK_EX flag fight with SWOOLE COROUTINE
        if (file_put_contents($file, $str, FILE_APPEND) === false) {
            trigger_error('Write log to file failed: ' . $file, E_USER_WARNING);
        }
    }

    /**
     * @param \ManaPHP\Logging\Logger\Log[] $logs
     *
     * @return void
     */
    public function append($logs)
    {
        $str = '';
        foreach ($logs as $log) {
            $s = $this->format($log);
            $str = $str === '' ? $s : $str . $s;
        }

        $this->write($str);
    }
}