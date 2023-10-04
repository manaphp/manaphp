<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client\Event;

use ManaPHP\Http\Client\Request;
use ManaPHP\Http\ClientInterface;

class HttpClientStart
{
    public function __construct(
        public ClientInterface $client,
        public string $method,
        public string|array $url,
        public Request $request
    ) {

    }
}