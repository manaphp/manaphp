<?php

namespace ManaPHP\Http\Dispatcher;

use ManaPHP\Event\EventArgs;

/**
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class Tracer extends \ManaPHP\Event\Tracer
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

        $this->response->setHeader('X-Dispatcher-Tracer', get_class($controller) . '::' . $action . 'Action');
    }
}