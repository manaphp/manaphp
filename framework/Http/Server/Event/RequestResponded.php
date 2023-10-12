<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;

#[Verbosity(Verbosity::HIGH)]
class RequestResponded
{
    public function __construct(public RequestInterface $request, public ResponseInterface $response)
    {

    }
}