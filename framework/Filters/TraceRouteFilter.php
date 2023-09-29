<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filters;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestAuthorized;

class TraceRouteFilter
{
    #[Autowired] protected ResponseInterface $response;

    public function onRequestAuthorized(#[Event] RequestAuthorized $event): void
    {
        $controller = $event->controller;
        $action = $event->action;

        $this->response->setHeader(
            'X-Router-Route', $controller::class . '::' . $action . 'Action'
        );
    }
}