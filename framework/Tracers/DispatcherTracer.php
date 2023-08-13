<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Event\EventArgs;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Tracer;

class DispatcherTracer extends Tracer
{
    #[Inject]
    protected ResponseInterface $response;

    public function listen(): void
    {
        $this->attachEvent('request:authorized', [$this, 'onRequestAuthorized']);
    }

    public function onRequestAuthorized(EventArgs $eventArgs): void
    {
        $controller = $eventArgs->data['controller'];
        $action = $eventArgs->data['action'];

        $this->response->setHeader(
            'X-Dispatcher-Tracer', $controller::class . '::' . $action . 'Action'
        );
    }
}