<?php

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class AmqpClientTracer extends Tracer
{
    public function listen()
    {
        $this->attachEvent('amqpClient:publish', [$this, 'onPublish']);
        $this->attachEvent('amqpClient:consuming', [$this, 'onConsuming']);
    }

    public function onPublish(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'amqpClient.publish');
    }

    public function onConsuming(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'amqpClient.consuming');
    }
}