<?php

namespace ManaPHP\Ws\Pushing\Server;

use ManaPHP\Event\EventArgs;

class Tracer extends \ManaPHP\Event\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->attachEvent('wspServer:pushing', [$this, 'onPushing']);
    }

    public function onPushing(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data);
    }
}