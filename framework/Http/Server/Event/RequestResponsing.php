<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;

class RequestResponsing
{
    public function __construct(public RequestInterface $request, public ResponseInterface $response)
    {

    }
}