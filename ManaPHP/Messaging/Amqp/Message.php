<?php

namespace ManaPHP\Messaging\Amqp;

use JsonSerializable;

class Message implements JsonSerializable
{
    /**
     * @var \ManaPHP\Messaging\Amqp
     */
    protected $amqp;
    /**
     * @var string
     */
    protected $queue;

    /**
     * @var \AMQPEnvelope
     */
    protected $envelope;

    /**
     * @param \ManaPHP\Messaging\Amqp $amqp
     * @param string                  $queue
     * @param \AMQPEnvelope           $envelope
     */
    public function __construct($amqp, $queue, $envelope)
    {
        $this->amqp = $amqp;
        $this->queue = $queue;
        $this->envelope = $envelope;
    }

    /**
     * @return \ManaPHP\Messaging\Amqp
     */
    public function getAmqp()
    {
        return $this->amqp;
    }

    /**
     * @return \AMQPEnvelope
     */
    public function getEnvelope()
    {
        return $this->envelope;
    }

    /**
     * @return string
     */
    public function getExchange()
    {
        return $this->envelope->getExchangeName();
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
    public function getRoutingKey()
    {
        return $this->envelope->getRoutingKey();
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
    public function getDeliveryTag()
    {
        return $this->envelope->getDeliveryTag();
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->envelope->getMessageId();
    }

    /**
     * @return string
     */
    public function getReplyTo()
    {
        return $this->envelope->getReplyTo();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->envelope->getType();
    }

    /**
     * @return bool
     */
    public function isRedelivery()
    {
        return $this->envelope->isRedelivery();
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->envelope->getHeaders();
    }

    /**
     * @param bool $multiple
     *
     * @return void
     */
    public function ack($multiple = false)
    {
        $this->amqp->ackMessage($this, $multiple);
    }

    /**
     * @param bool $multiple
     *
     * @return void
     */
    public function nack($multiple = false)
    {
        $this->amqp->nackMessage($this, $multiple);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $envelope = $this->envelope;

        $data = [];
        $data['exchange'] = $envelope->getExchangeName();
        $data['routing_key'] = $envelope->getRoutingKey();
        $data['queue'] = $this->queue;
        $data['is_redelivery'] = $envelope->isRedelivery();
        $data['delivery_tag'] = $envelope->getDeliveryTag();
        $data['content_type'] = $envelope->getContentType();
        if ($data['content_type'] === 'application/json') {
            $data['body'] = json_parse($envelope->getBody());
            if ($data['body'] === null) {
                $data['body'] = $envelope->getBody();
            }
        } else {
            $data['body'] = $envelope->getBody();
        }

        return $data;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_stringify($this->toArray());
    }
}