<?php

namespace ManaPHP\Http\Dispatcher;

use ManaPHP\Event\EventArgs;
use ManaPHP\Helper\Reflection;

/**
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class Tracer extends \ManaPHP\Tracing\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->attachEvent('request:authorize', [$this, 'onRequestAuthorize']);
    }

    public function onRequestAuthorize(EventArgs $eventArgs)
    {
        $controller = $eventArgs->data['controller'];
        $action = $eventArgs->data['action'];

        $this->response->setHeader(
            'X-Dispatcher-Tracer', Reflection::getClass($controller) . '::' . $action . 'Action'
        );
    }
}