<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

/**
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class DispatcherTracer extends Tracer
{
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