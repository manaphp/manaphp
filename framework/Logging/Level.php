<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use JetBrains\PhpStorm\ArrayShape;

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

    #[ArrayShape([self::EMERGENCY => "int", self::ALERT => "int", self::CRITICAL => "int", self::ERROR => "int",
                  self::WARNING   => "int", self::NOTICE => "int", self::INFO => "int", self::DEBUG => "int"])]
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