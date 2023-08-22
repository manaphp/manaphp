<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

use ManaPHP\Http\ClientInterface;

class HttpClientError
{
    public function __construct(
        public ClientInterface $client,
        public string $method,
        public string|array $url,
        public Request $request,
        public ?Response $response,
    ) {

    }
}