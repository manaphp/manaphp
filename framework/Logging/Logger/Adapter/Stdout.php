<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Level;
use ManaPHP\Logging\Logger\Log;

class Stdout extends AbstractLogger
{
    protected string $format;

    public function __construct(string $format = '[:date][:level][:category][:location] :message',
        string $level = Level::DEBUG, ?string $hostname = null
    ) {
        parent::__construct($level, $hostname);

        $this->format = $format;
    }

    public function append(Log $log): void
    {
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