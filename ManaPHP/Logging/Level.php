<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

class Level
{
    public const EMERGENCY = 'emergency';
    public const ALERT = 'alert';
    public const CRITICAL = 'critical';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';
    public const INFO = 'info';
    public const DEBUG = 'debug';

    public static function map(): array
    {
        return [self::EMERGENCY => 0,
                self::ALERT     => 1,
                self::CRITICAL  => 2,
                self::ERROR     => 3,
                self::WARNING   => 4,
                self::NOTICE    => 5,
                self::INFO      => 6,
                self::DEBUG     => 7,
        ];
    }
}