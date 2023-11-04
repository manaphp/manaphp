<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Http\RequestInterface;

#[Verbosity(Verbosity::LOW)]
class RequestBegin implements JsonSerializable
{
    public function __construct(public RequestInterface $request)
    {

    }

    public function jsonSerialize(): array
    {
        return [
            'method'    => $this->request->method(),
            'url'       => $this->request->url(),
            'query'     => $this->request->server('QUERY_STRING'),
            'client_ip' => $this->request->ip(),
        ];
    }
}