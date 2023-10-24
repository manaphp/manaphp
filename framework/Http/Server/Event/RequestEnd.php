<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;

#[Verbosity(Verbosity::LOW)]
class RequestEnd implements JsonSerializable
{
    public function __construct(
        public RequestInterface $request,
        public ResponseInterface $response,
    ) {

    }

    public function jsonSerialize(): array
    {
        return [
            'uri'            => $this->request->getUri(),
            'http_code'      => $this->response->getStatusCode(),
            'content-type'   => $this->response->getContentType(),
            'content-length' => $this->response->getContentLength(),
        ];
    }
}