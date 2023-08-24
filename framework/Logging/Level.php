<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use Psr\Log\LogLevel;

class Level
{
    public static function map(): array
    {
        return [LogLevel::EMERGENCY => 0,
                LogLevel::ALERT     => 1,
                LogLevel::CRITICAL  => 2,
                LogLevel::ERROR     => 3,
                LogLevel::WARNING   => 4,
                LogLevel::NOTICE    => 5,
                LogLevel::INFO      => 6,
                LogLevel::DEBUG     => 7,
        ];
    }
}