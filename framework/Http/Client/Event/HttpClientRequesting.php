<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

use JsonSerializable;
use ManaPHP\Http\ClientInterface;
use Stringable;

class HttpClientRequesting implements JsonSerializable, Stringable
{
    public function __construct(
        public ClientInterface $client,
        public string $method,
        public string|array $url,
        public Request $request
    ) {

    }

    public function jsonSerialize(): array
    {
        return ['url'     => $this->url,
                'method'  => $this->method,
                'body'    => $this->request->body,
                'headers' => $this->request->headers];
    }

    public function __toString(): string
    {
        return json_stringify($this->jsonSerialize());
    }
}