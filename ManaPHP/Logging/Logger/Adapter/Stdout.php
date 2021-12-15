<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\AbstractLogger;

class Stdout extends AbstractLogger
{
    protected string $format = '[:date][:level][:category][:location] :message';

    public function __construct(array $options = [])
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
    public function append(array $logs): void
    {
        foreach ($logs as $log) {
            $replaced = [];

            $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
            $replaced[':date'] = date('Y-m-d\TH:i:s', (int)$log->timestamp) . $ms;
            $replaced[':level'] = $log->level;
            $replaced[':category'] = $log->category;
            $replaced[':location'] = "$log->file:$log->line";
            $replaced[':message'] = $log->message;

            echo strtr($this->format, $replaced), PHP_EOL;
        }
    }
}