<?php

declare(strict_types=1);

namespace ManaPHP\Logging;

use Psr\Log\LogLevel;

class Level
{
    protected static array $map = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7,
    ];

    public static function map(): array
    {
        return self::$map;
    }

    public static function gt(string $l1, string $l2): bool
    {
        return (self::$map[$l1] ?? 7) > (self::$map[$l2] ?? 7);
    }
}
