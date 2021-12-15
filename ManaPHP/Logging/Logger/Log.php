<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger;

class Log
{
    public float $timestamp;
    public string $hostname;
    public string $client_ip;
    public string $request_id;
    public string $category;
    public string $file;
    public int $line;
    public string $level;
    public string $message;
}