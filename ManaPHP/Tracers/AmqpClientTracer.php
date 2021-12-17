<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class AmqpClientTracer extends Tracer
{
    public function listen(): void
    {
        $this->attachEvent('amqpClient:publish', [$this, 'onPublish']);
        $this->attachEvent('amqpClient:consuming', [$this, 'onConsuming']);
    }

    public function onPublish(EventArgs $eventArgs): void
    {
        $this->debug($eventArgs->data, 'amqpClient.publish');
    }

    public function onConsuming(EventArgs $eventArgs): void
    {
        $this->debug($eventArgs->data, 'amqpClient.consuming');
    }
}