<?php

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\AbstractLogger;

class Stdout extends AbstractLogger
{
    /**
     * @var string
     */
    protected $format = '[:date][:level][:category][:location] :message';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($options['format'])) {
            $this->format = $options['format'];
        }
    }

    /**
     * @param \ManaPHP\Logging\Logger\Log[] $logs
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

            echo strtr($this->format, $replaced), PHP_EOL;
        }
    }
}