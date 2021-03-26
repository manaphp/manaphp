<?php

namespace ManaPHP\Amqp\Engine\Php;

use ManaPHP\Amqp\MessageInterface;
use PhpAmqpLib\Message\AMQPMessage;
use JsonSerializable;

class Message implements MessageInterface, JsonSerializable
{
    /**
     * @var AMQPMessage
     */
    protected $envelope;

    /**
     * @var string
     */
    protected $queue;

    /**
     * @param AMQPMessage $envelope
     * @param string      $queue
     */
    public function __construct($envelope, $queue)
    {
        $this->envelope = $envelope;
        $this->queue = $queue;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->envelope->getBody();
    }

    /**
     * @return array
     */
    public function getJsonBody()
    {
        return json_parse($this->envelope->getBody());
    }

    /**
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @return string
     */
    public function getExchange()
    {
        return $this->envelope->getExchange();
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->envelope->getRoutingKey();
    }

    /**
     * @return int
     */
    public function getDeliveryTag()
    {
        return $this->envelope->getDeliveryTag();
    }

    /**
     * @return bool
     */
    public function isRedelivered()
    {
        return $this->envelope->isRedelivered();
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->envelope->get_properties();
    }

    /**
     * @return string
     */
    public function getReplyTo()
    {
        return $this->envelope->get('reply_to');
    }

    /**
     * @return array
     */
    public function jsonSerialize()
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