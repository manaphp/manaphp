<?php
declare(strict_types=1);

namespace ManaPHP\Amqp\Engine\Php;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use ManaPHP\Amqp\MessageInterface;
use PhpAmqpLib\Message\AMQPMessage;

class Message implements MessageInterface, JsonSerializable
{
    protected AMQPMessage $envelope;
    protected string $queue;

    public function __construct(AMQPMessage $envelope, string $queue)
    {
        $this->envelope = $envelope;
        $this->queue = $queue;
    }

    public function getBody(): string
    {
        return $this->envelope->getBody();
    }

    public function getJsonBody(): array
    {
        return json_parse($this->envelope->getBody());
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function getExchange(): string
    {
        return $this->envelope->getExchange();
    }

    public function getRoutingKey(): string
    {
        return $this->envelope->getRoutingKey();
    }

    public function getDeliveryTag(): int
    {
        return $this->envelope->getDeliveryTag();
    }

    public function isRedelivered(): bool
    {
        return $this->envelope->isRedelivered();
    }

    public function getProperties(): array
    {
        return $this->envelope->get_properties();
    }

    public function getReplyTo(): string
    {
        return $this->envelope->get('reply_to');
    }

    #[ArrayShape(['queue'         => 'string',
                  'exchange'      => 'mixed',
                  'routingKey'    => 'mixed',
                  'deliveryTag'   => 'mixed',
                  'isRedelivered' => 'mixed',
                  'body'          => 'mixed',
                  'properties'    => 'mixed'])]
    public function jsonSerialize(): array
    {
        return [
            'queue'         => $this->queue,
            'exchange'      => $this->envelope->getExchange(),
            'routingKey'    => $this->envelope->getRoutingKey(),
            'deliveryTag'   => $this->envelope->getDeliveryTag(),
            'isRedelivered' => $this->envelope->isRedelivered(),
            'body'          => $this->envelope->getBody(),
            'properties'    => $this->envelope->get_properties(),
        ];
    }
}