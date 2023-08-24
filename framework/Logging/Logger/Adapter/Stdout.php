<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Di\Attribute\Value;
use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Logger\Log;

class Stdout extends AbstractLogger
{
    #[Value] protected string $line_format = '[:time][:level][:category][:location] :message';

    public function append(Log $log): void
    {
        $replaced = [];

        preg_match_all('#:(\w+)#', $this->line_format, $matches);
        foreach ($matches[1] as $key) {
            $replaced[":$key"] = $log->$key ?? '-';
        }

        echo strtr($this->line_format, $replaced), PHP_EOL;
    }
}