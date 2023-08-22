<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestAuthorized;
use ManaPHP\Tracer;

class DispatcherTracer extends Tracer
{
    #[Inject] protected ResponseInterface $response;

    public function onRequestAuthorized(#[Event] RequestAuthorized $event): void
    {
        $controller = $event->controller;
        $action = $event->action;

        $this->response->setHeader(
            'X-Dispatcher-Tracer', $controller::class . '::' . $action . 'Action'
        );
    }
}