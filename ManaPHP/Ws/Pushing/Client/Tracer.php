<?php

namespace ManaPHP\Ws\Pushing\Client;

use ManaPHP\Event\EventArgs;

class Tracer extends \ManaPHP\Tracing\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->attachEvent('wspClient:push', [$this, 'onPush']);
    }

    public function onPush(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'wspClient.push');
    }
}