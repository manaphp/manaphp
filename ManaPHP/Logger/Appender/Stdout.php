<?php

namespace ManaPHP\Logger\Appender;

use ManaPHP\Component;
use ManaPHP\Logger\AppenderInterface;

/**
 * Class ManaPHP\Logger\Appender\Stdout
 *
 * @package logger
 */
class Stdout extends Component implements AppenderInterface
{
    /**
     * @var string
     */
    protected $_format = '[:date][:level][:category][:location] :message';

    /**
     * \ManaPHP\Logger\Adapter\File constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
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
        $replaced = [];

        $replaced[':date'] = date('Y-m-d\TH:i:s', $log->timestamp) . sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
        $replaced[':level'] = $log->level;
        $replaced[':category'] = $log->category;
        $replaced[':location'] = $log->location;
        $replaced[':message'] = $log->message;

        echo strtr($this->_format, $replaced), PHP_EOL;
    }
}