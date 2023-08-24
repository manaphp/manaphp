<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger;

use stdClass;

class Log extends stdClass
{
    public string $time;
    public float $timestamp;
    public string $hostname;
    public string $category;
    public string $file;
    public int $line;
    public string $location;
    public string $level;
    public string $message;
}