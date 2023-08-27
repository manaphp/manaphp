<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Amqp\Client\Event\AmqpClientConsuming;
use ManaPHP\Amqp\Client\Event\AmqpClientPublish;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\Attribute\Event;
use Psr\Log\LoggerInterface;

class AmqpClientTracer
{
    #[Inject] protected LoggerInterface $logger;

    public function onPublish(#[Event] AmqpClientPublish $event): void
    {
        $this->logger->debug($event, ['category' => 'amqpClient.publish']);
    }

    public function onConsuming(#[Event] AmqpClientConsuming $event): void
    {
        $this->logger->debug($event, ['category' => 'amqpClient.consuming']);
    }
}