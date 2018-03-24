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
     * @param array $logEvent
     *
     * @return void
     */
    public function append($logEvent)
    {
        $logEvent['date'] = date('Y-m-d H:i:s', $logEvent['timestamp']);

        $logEvent['message'] .= PHP_EOL;

        $replaced = [];
        foreach ($logEvent as $k => $v) {
            $replaced[":$k"] = $v;
        }

        echo strtr($this->_format, $replaced), PHP_EOL;
    }
}