<?php

namespace ManaPHP\Messaging;

use AMQPChannel;
use AMQPConnection;
use AMQPEnvelope;
use AMQPExchange;
use AMQPQueue;
use Exception;
use ManaPHP\Component;
use ManaPHP\Exception\DsnFormatException;
use ManaPHP\Exception\InvalidKeyException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Messaging\Amqp\ConnectionException;
use ManaPHP\Messaging\Amqp\Exception as AmqpException;

class Amqp extends Component implements AmqpInterface
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var \AMQPConnection
     */
    protected $connection;

    /**
     * @var \AMQPChannel
     */
    protected $channel;

    /**
     * @var \AMQPExchange[]
     */
    protected $exchanges = [];

    /**
     * @var \AMQPQueue[]
     */
    protected $queues = [];

    const MESSAGE_METADATA = '_metadata_';

    /**
     * @param string $uri
     */
    public function __construct($uri = null)
    {
        $this->uri = $uri;

        $credentials = [];

        $query = [];

        if ($uri) {
            $parts = parse_url($uri);

            if ($parts['scheme'] !== 'amqp') {
                throw new DsnFormatException(['`%s` scheme is unknown: `%s`', $parts['scheme'], $uri]);
            }

            if (isset($parts['host'])) {
                $credentials['host'] = $parts['host'];
            }

            if (isset($parts['port'])) {
                $credentials['port'] = $parts['port'];
            }

            if (isset($parts['user'])) {
                $credentials['login'] = $parts['user'];
            }

            if (isset($parts['pass'])) {
                $credentials['password'] = $parts['pass'];
            }

            if (isset($parts['path'])) {
                $credentials['vhost'] = $parts['path'];
            }

            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);
            }
        }

        try {
            $this->connection = new AMQPConnection($credentials);

            if (isset($query['persistent']) && $query['persistent']) {
                $r = $this->connection->pconnect();
            } else {
                $r = $this->connection->connect();
            }

            if (!$r) {
                throw new ConnectionException(['connect to `:uri` amqp broker failed', 'uri' => $this->uri]);
            }
        } catch (Exception $e) {
            throw new ConnectionException(['connect to `%s` amqp broker failed: %s', $this->uri, $e->getMessage()]);
        }

        try {
            $this->channel = new AMQPChannel($this->connection);
        } catch (Exception $e) {
            throw new ConnectionException(['create channel with `%s` uri failed: %s', $this->uri, $e->getMessage()]);
        }
        try {
            $this->exchanges[''] = new AMQPExchange($this->channel);
        } catch (Exception $e) {
            throw new AmqpException('create default exchange instance failed');
        }

        if (isset($query['prefetch_count'])) {
            $this->qos($query['prefetch_count']);
        }
    }

    /**
     * @return \AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param int $count
     * @param int $size
     *
     * @return static
     */
    public function qos($count, $size = 0)
    {
        try {
            $this->channel->qos($size, $count);
        } catch (Exception $e) {
            throw new AmqpException('set the Quality Of Service settings for the channel failed');
        }

        return $this;
    }

    /**
     * @param string $name
     * @param int    $flags support the following flags: AMQP_DURABLE, AMQP_PASSIVE.
     * @param string $type
     *
     * @return \AMQPExchange
     */
    public function declareExchange($name, $type = AMQP_EX_TYPE_DIRECT, $flags = AMQP_DURABLE)
    {
        if (isset($this->exchanges[$name])) {
            throw new InvalidKeyException(['declare `%s` exchange failed: it is exists already', $name]);
        }

        try {
            $exchange = new AMQPExchange($this->channel);

            $exchange->setName($name);
            $exchange->setType($type);
            $exchange->setFlags($flags);

            if (!$exchange->declareExchange()) {
                throw new AmqpException(['declare `:exchange` exchange failed', 'exchange' => $name]);
            }
        } catch (Exception $e) {
            throw new AmqpException(['declare `%s` exchange failed: `%s`', $name, $e->getMessage()]);
        }

        $this->exchanges[$name] = $exchange;

        return $exchange;
    }

    /**
     * @param bool $name_only
     *
     * @return \AMQPExchange[]|string[]
     */
    public function getExchanges($name_only = true)
    {
        if ($name_only) {
            return array_keys($this->exchanges);
        } else {
            return $this->exchanges;
        }
    }

    /**
     * @param string $name
     * @param int    $flags Optionally AMQP_IFUNUSED can be specified to indicate the exchange should not be deleted
     *                      until no clients are connected to it.
     *
     * @return static
     */
    public function deleteExchange($name, $flags = AMQP_NOPARAM)
    {
        if (!isset($this->exchanges[$name])) {
            throw new InvalidKeyException(['delete `%s` exchange failed: it is NOT exists', $name]);
        }

        try {
            $this->exchanges[$name]->delete($flags);
        } catch (Exception $e) {
            throw new AmqpException(['delete `%s` exchange failed: %s', $name, $e->getMessage()]);
        }

        unset($this->exchanges[$name]);

        return $this;
    }

    /**
     * @param string $name
     * @param int    $flags
     *
     * @return \AMQPQueue
     */
    public function declareQueue($name, $flags = AMQP_DURABLE)
    {
        if (isset($this->queues[$name])) {
            throw new InvalidKeyException(['declare `:queue` queue failed: it is exists already', 'queue' => $name]);
        }

        try {
            $queue = new AMQPQueue($this->channel);

            $queue->setName($name);
            $queue->setFlags($flags);

            $queue->declareQueue();
        } catch (Exception $e) {
            throw new AmqpException(['declare `%s` queue failed: `%s`', $name, $e->getMessage()]);
        }

        $this->queues[$name] = $queue;

        return $queue;
    }

    /**
     * @param bool $name_only
     *
     * @return \AMQPQueue[]|string[]
     */
    public function getQueues($name_only = true)
    {
        if ($name_only) {
            return array_keys($this->queues);
        } else {
            return $this->queues;
        }
    }

    /**
     * @param string $queue
     * @param string $exchange
     * @param string $binding_key
     *
     * @return static
     */
    public function bindQueue($queue, $exchange, $binding_key = '')
    {
        if (!isset($this->queues[$queue])) {
            throw new InvalidKeyException(
                [
                    'bind `:queue` queue to `:exchange` exchange with `:binding_key` binding key failed: queue is NOT exists',
                    'queue'       => $queue,
                    'exchange'    => $exchange,
                    'binding_key' => $binding_key
                ]
            );
        }

        if (!isset($this->exchanges[$exchange])) {
            throw new AmqpException(
                [
                    'bind `:queue` queue to `:exchange` exchange with `:binding_key` binding key failed: exchange is NOT exists',
                    'queue'       => $queue,
                    'exchange'    => $exchange,
                    'binding_key' => $binding_key
                ]
            );
        }

        try {
            $this->queues[$queue]->bind($exchange, $binding_key);
        } catch (Exception $e) {
            throw new AmqpException(
                [
                    'bind `:queue` queue to `:exchange` exchange with `:binding_key` binding key failed: :error',
                    'queue'       => $queue,
                    'exchange'    => $exchange,
                    'binding_key' => $binding_key,
                    'error'       => $e->getMessage()
                ]
            );
        }

        return $this;
    }

    /**
     *  Purge the contents of a queue
     *
     * @param string $name
     *
     * @return static
     */
    public function purgeQueue($name)
    {
        if (!isset($this->queues[$name])) {
            throw new InvalidKeyException(['purge `:queue` queue failed: it is NOT exists', 'queue' => $name]);
        }

        try {
            $this->queues[$name]->purge();
        } catch (Exception $e) {
            throw new AmqpException(['purge `%s` queue failed: %s', $name, $e->getMessage()]);
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function deleteQueue($name)
    {
        if (!isset($this->queues)) {
            throw new InvalidKeyException(['delete `%s` queue failed: it is not exists', $name]);
        }

        try {
            $this->queues[$name]->delete();
        } catch (Exception $e) {
            throw new AmqpException(['delete `%s` queue failed: %s', $name, $e->getMessage()]);
        }

        unset($this->queues[$name]);

        return $this;
    }

    /**
     * @param string $message
     * @param string $exchange
     * @param string $routing_key
     * @param int    $flags One or more of AMQP_MANDATORY and AMQP_IMMEDIATE
     * @param array  $attributes
     *
     * @return static
     */
    public function publishMessage($message, $exchange, $routing_key = '', $flags = AMQP_NOPARAM, $attributes = [])
    {
        if (!isset($this->exchanges[$exchange])) {
            throw new InvalidKeyException(
                ['publish message to `:1` exchange with `:2` routing_key failed: exchange is NOT exists', $exchange,
                 $routing_key]
            );
        }

        try {
            $this->exchanges[$exchange]->publish($message, $routing_key, $flags, $attributes);
        } catch (Exception $e) {
            throw new AmqpException(
                ['publish message to `:1` exchange with `:2` routing_key failed: `:3`', $exchange, $routing_key,
                 $e->getMessage()]
            );
        }

        return $this;
    }

    /**
     * @param array|\JsonSerializable $message
     * @param string                  $exchange
     * @param string                  $routing_key
     * @param int                     $flags One or more of AMQP_MANDATORY and AMQP_IMMEDIATE
     * @param array                   $attributes
     *
     * @return static
     */
    public function publishJsonMessage($message, $exchange, $routing_key = '', $flags = AMQP_NOPARAM, $attributes = [])
    {
        $attributes['content_type'] = 'application/json';

        return $this->publishMessage(json_stringify($message), $exchange, $routing_key, $flags, $attributes);
    }

    /**
     * @param string $queue
     * @param bool   $auto_ack
     *
     * @return false|\ManaPHP\Messaging\Amqp\Message
     */
    public function getMessage($queue, $auto_ack = false)
    {
        if (!isset($this->queues[$queue])) {
            throw new InvalidKeyException(['retrieve message from queue failed: `%s` queue is NOT exists`', $queue]);
        }

        try {
            $envelope = $this->queues[$queue]->get($auto_ack ? AMQP_AUTOACK : AMQP_NOPARAM);
        } catch (Exception $e) {
            throw new AmqpException(['retrieve message from `%s` queue failed: %s', $queue, $e->getMessage()]);
        }

        return $envelope === false ? false : $this->getNew('ManaPHP\Amqp\Message', [$this, $queue, $envelope]);
    }

    /**
     * @param string $queue
     * @param bool   $auto_ack
     *
     * @return false|array
     */
    public function getJsonMessage($queue, $auto_ack = false)
    {
        if (!isset($this->queues[$queue])) {
            throw new InvalidKeyException(['retrieve message from queue failed: `%s` queue is NOT exists', $queue]);
        }

        try {
            $envelope = $this->queues[$queue]->get($auto_ack ? AMQP_AUTOACK : AMQP_NOPARAM);
        } catch (Exception $e) {
            throw new AmqpException(['retrieve message from `%s` queue failed: %s', $queue, $e->getMessage()]);
        }

        if ($envelope !== false) {
            $json = json_parse($envelope->getBody());
            $json[self::MESSAGE_METADATA] = [
                'queue'         => $queue,
                'delivery_tag'  => $envelope->getDeliveryTag(),
                'is_redelivery' => $envelope->isRedelivery()
            ];

            return $json;
        } else {
            return false;
        }
    }

    /**
     * @param \ManaPHP\Messaging\Amqp\Message|array $message
     * @param bool                                  $multiple
     *
     * @return static
     */
    public function ackMessage($message, $multiple = false)
    {
        if (is_array($message)) {
            if (!isset($message[self::MESSAGE_METADATA])) {
                throw new InvalidKeyException(['ack message failed: message not contains metadata information']);
            }
            $queue = $message[self::MESSAGE_METADATA]['queue'];
            $delivery_tag = $message[self::MESSAGE_METADATA]['delivery_tag'];
        } else {
            $queue = $message->getQueue();
            $delivery_tag = $message->getDeliveryTag();
        }

        if (!$this->queues[$queue]) {
            throw new InvalidKeyException(['ack message failed: `:queue` queue is NOT exists', 'queue' => $queue]);
        }
        try {
            $this->queues[$queue]->ack($delivery_tag, $multiple ? AMQP_MULTIPLE : AMQP_NOPARAM);
        } catch (Exception $e) {
            throw new AmqpException(['ack `%s` queue message failed: %s', $queue, $e->getMessage()]);
        }

        return $this;
    }

    /**
     * @param \ManaPHP\Messaging\Amqp\Message|array $message
     * @param bool                                  $multiple
     *
     * @return static
     */
    public function nackMessage($message, $multiple = false)
    {
        if (is_array($message)) {
            if (!isset($message[self::MESSAGE_METADATA])) {
                throw new InvalidValueException(['ack message failed: message not contains metadata information']);
            }
            $queue = $message[self::MESSAGE_METADATA]['queue'];
            $delivery_tag = $message[self::MESSAGE_METADATA]['delivery_tag'];
        } else {
            $queue = $message->getQueue();
            $delivery_tag = $message->getDeliveryTag();
        }

        if (!$this->queues[$queue]) {
            throw new InvalidKeyException(['nack message failed: `:queue` queue is NOT exists', 'queue' => $queue]);
        }
        try {
            $this->queues[$queue]->nack($delivery_tag, $multiple ? AMQP_MULTIPLE : AMQP_NOPARAM);
        } catch (Exception $e) {
            throw new AmqpException(['nack `%s` queue message failed: %s', $queue, $e->getMessage()]);
        }

        return $this;
    }

    /**
     * @param string   $queue
     * @param callable $callback
     * @param int      $flags
     *
     * @return void
     */
    public function consumeMessages($queue, $callback, $flags = AMQP_NOPARAM)
    {
        if (!isset($this->queues[$queue])) {
            throw new InvalidKeyException(['consume message from queue failed: `%s` queue is NOT exists', $queue]);
        }

        try {
            $this->queues[$queue]->consume(
                function (AMQPEnvelope $envelope) use ($callback, $queue) {
                    return $callback($this->getNew('ManaPHP\Amqp\Message', [$this, $queue, $envelope]));
                }, $flags
            );
        } catch (Exception $e) {
            throw new AmqpException('consume `:queue` queue message failed: ', $e->getMessage());
        }
    }
}