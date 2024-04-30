<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Server\Event\RequestBegin;
use function bin2hex;
use function random_bytes;

class RequestIdMiddleware
{
    #[Autowired] protected RequestInterface $request;

    public function onBegin(#[Event] RequestBegin $event): void
    {
        if ($this->request->header('x-request-id') === null) {
            $this->request->getContext()->_SERVER['HTTP_X_REQUEST_ID'] = bin2hex(random_bytes(16));
        }
    }
}