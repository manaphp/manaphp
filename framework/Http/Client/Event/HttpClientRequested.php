<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

use JsonSerializable;
use ManaPHP\Http\ClientInterface;
use Stringable;

class HttpClientRequested implements JsonSerializable, Stringable
{
    public function __construct(
        public ClientInterface $client,
        public string $method,
        public string|array $url,
        public Request $request,
        public Response $response,
    ) {

    }

    public function jsonSerialize(): array
    {
        return [
            'url'      => $this->url,
            'method'   => $this->method,
            'response' => $this->response->jsonSerialize(),
        ];
    }

    public function __toString(): string
    {
        return json_stringify($this->jsonSerialize());
    }
}