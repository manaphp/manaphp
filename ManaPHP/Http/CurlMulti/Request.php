<?php
declare(strict_types=1);

namespace ManaPHP\Http\CurlMulti;

class Request
{
    public string $method = 'GET';
    public string|array $url;
    public string|array $body = [];
    public array $headers = [];
    public array $options = [];
    public array $meta = [];
    public mixed $callbacks;
    public float $start_time;

    public function __construct(string|array $url, ?callable $callbacks = null, string $method = 'GET', string|array $body = null)
    {
        $this->url = $url;
        $this->callbacks = $callbacks;
        $this->method = $method;
        $this->body = $body;
    }
}