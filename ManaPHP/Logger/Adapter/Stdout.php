<?php

namespace ManaPHP\Logger\Adapter;

use ManaPHP\Logger;

/**
 * Class ManaPHP\Logger\Adapter\Stdout
 *
 * @package logger
 */
class Stdout extends Logger
{
    /**
     * @var string
     */
    protected $_format = '[:date][:level][:category][:location] :message';

    /**
     * Stdout constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($options['format'])) {
            $this->_format = $options['format'];
        }
    }

    /**
     * @param \ManaPHP\Logger\Log[] $logs
     *
     * @return void
     */
    public function append($logs)
    {
        foreach ($logs as $log) {
            $replaced = [];

            $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
            $replaced[':date'] = date('Y-m-d\TH:i:s', $log->timestamp) . $ms;
            $replaced[':level'] = $log->level;
            $replaced[':category'] = $log->category;
            $replaced[':location'] = "$log->file:$log->line";
            $replaced[':message'] = $log->message;

            echo strtr($this->_format, $replaced), PHP_EOL;
        }
    }
}