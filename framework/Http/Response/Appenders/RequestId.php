<?php
declare(strict_types=1);

namespace ManaPHP\Http\Response\Appenders;

use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response\AppenderInterface;
use ManaPHP\Http\ResponseInterface;

class RequestId implements AppenderInterface
{
    public function append(RequestInterface $request, ResponseInterface $response): void
    {
        $response->setHeader('X-Request-Id', $request->getRequestId());
    }
}