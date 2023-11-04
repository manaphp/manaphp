<?php
declare(strict_types=1);

namespace ManaPHP\Http\Response\Appenders;

use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response\AppenderInterface;
use ManaPHP\Http\ResponseInterface;

class RequestIdAppender implements AppenderInterface
{
    public function append(RequestInterface $request, ResponseInterface $response): void
    {
        if (($x_request_id = $request->header('x-request-id')) !== null) {
            $response->setHeader('X-Request-Id', $x_request_id);
        }
    }
}