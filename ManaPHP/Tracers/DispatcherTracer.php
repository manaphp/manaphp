<?php

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

/**
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class DispatcherTracer extends Tracer
{
    public function listen()
    {
        $this->attachEvent('request:authorize', [$this, 'onRequestAuthorize']);
    }

    public function onRequestAuthorize(EventArgs $eventArgs)
    {
        $controller = $eventArgs->data['controller'];
        $action = $eventArgs->data['action'];

        $this->response->setHeader(
            'X-Dispatcher-Tracer', get_class($controller) . '::' . $action . 'Action'
        );
    }
}