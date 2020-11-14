<?php

namespace ManaPHP\Messaging\Amqp;

use JsonSerializable;

class Message implements JsonSerializable
{
    /**
     * @var \ManaPHP\Messaging\Amqp
     */
    protected $_amqp;
    /**
     * @var string
     */
    protected $_queue;

    /**
     * @var \AMQPEnvelope
     */
    protected $_envelope;

    /**
     * Message constructor.
     *
     * @param \ManaPHP\Messaging\Amqp $amqp
     * @param string                  $queue
     * @param \AMQPEnvelope           $envelope
     */
    public function __construct($amqp, $queue, $envelope)
    {
        $this->_amqp = $amqp;
        $this->_queue = $queue;
        $this->_envelope = $envelope;
    }

    /**
     * @return \ManaPHP\Messaging\Amqp
     */
    public function getAmqp()
    {
        return $this->_amqp;
    }

    /**
     * @return \AMQPEnvelope
     */
    public function getEnvelope()
    {
        return $this->_envelope;
    }

    /**
     * @return string
     */
    public function getExchange()
    {
        return $this->_envelope->getExchangeName();
    }

    /**
     * @return string
     */
    public function getQueue()
    {
        return $this->_queue;
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->_envelope->getRoutingKey();
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->_envelope->getBody();
    }

    /**
     * @return array
     */
    public function getJsonBody()
    {
        return json_parse($this->_envelope->getBody());
    }

    /**
     * @return string
     */
    public function getDeliveryTag()
    {
        return $this->_envelope->getDeliveryTag();
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->_envelope->getMessageId();
    }

    /**
     * @return string
     */
    public function getReplyTo()
    {
        return $this->_envelope->getReplyTo();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_envelope->getType();
    }

    /**
     * @return bool
     */
    public function isRedelivery()
    {
        return $this->_envelope->isRedelivery();
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->_envelope->getHeaders();
    }

    /**
     * @param bool $multiple
     */
    public function ack($multiple = false)
    {
        $this->_amqp->ackMessage($this, $multiple);
    }

    /**
     * @param bool $multiple
     */
    public function nack($multiple = false)
    {
        $this->_amqp->nackMessage($this, $multiple);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $envelope = $this->_envelope;

        $data = [];
        $data['exchange'] = $envelope->getExchangeName();
        $data['routing_key'] = $envelope->getRoutingKey();
        $data['queue'] = $this->_queue;
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