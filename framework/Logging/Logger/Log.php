<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger;

use ManaPHP\Logging\Logger;
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

    public function __construct(string $level, string $hostname, string $time_format)
    {
        $this->level = $level;
        $this->hostname = $hostname;

        $this->timestamp = $timestamp = microtime(true);
        if (str_contains($time_format, Logger::MILLISECONDS)) {
            $ms = sprintf('%03d', ($this->timestamp - (int)$timestamp) * 1000);
            $time_format = str_replace(Logger::MILLISECONDS, $ms, $time_format);
        } elseif (str_contains($time_format, Logger::MICROSECONDS)) {
            $ms = sprintf('%06d', ($this->timestamp - (int)$timestamp) * 1000000);
            $time_format = str_replace(Logger::MICROSECONDS, $ms, $time_format);
        }

        $this->time = date($time_format, (int)$timestamp);
    }

    public function setLocation(array $trace): void
    {
        $this->file = isset($trace['file']) ? basename($trace['file']) : '-';
        $this->line = $trace['line'] ?? 0;

        $this->location = $this->file . ':' . $this->line;
    }
}