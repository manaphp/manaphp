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
        $this->attachEvent('request:authorize', [$this, 'onRequestAuthorize']);
    }

    public function onRequestAuthorize(EventArgs $eventArgs): void
    {
        $controller = $eventArgs->data['controller'];
        $action = $eventArgs->data['action'];

        $this->response->setHeader(
            'X-Dispatcher-Tracer', get_class($controller) . '::' . $action . 'Action'
        );
    }
}