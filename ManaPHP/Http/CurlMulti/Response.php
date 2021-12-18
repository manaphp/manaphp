<?php
declare(strict_types=1);

namespace ManaPHP\Http\CurlMulti;

class Response
{
    public int $http_code;
    public string $body;
    public array $headers = [];
    public string $file;
    public float $process_time;
    public string $content_type;
    public array $stats;
    public Request $request;
}