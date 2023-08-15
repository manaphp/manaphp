<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Di\Attribute\Value;
use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Logger\Log;

class Stdout extends AbstractLogger
{
    #[Value] protected string $format = '[:date][:level][:category][:location] :message';

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