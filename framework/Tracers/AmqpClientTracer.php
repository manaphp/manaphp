<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Amqp\Client\Event\AmqpClientConsuming;
use ManaPHP\Amqp\Client\Event\AmqpClientPublish;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Tracer;

class AmqpClientTracer extends Tracer
{
    public function onPublish(#[Event] AmqpClientPublish $event): void
    {
        $this->debug($event, 'amqpClient.publish');
    }

    public function onConsuming(#[Event] AmqpClientConsuming $event): void
    {
        $this->debug($event, 'amqpClient.consuming');
    }
}