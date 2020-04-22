<?php

namespace ManaPHP;

interface AmqpInterface
{
    /**
     * @return \AMQPChannel
     */
    public function getChannel();

    /**
     * @param int $count
     * @param int $size
     *
     * @return static
     */
    public function qos($count, $size = 0);

    /**
     * @param string $name
     * @param int    $flags support the following flags: AMQP_DURABLE, AMQP_PASSIVE.
     * @param string $type
     *
     * @return \AMQPExchange
     */
    public function declareExchange($name, $type = AMQP_EX_TYPE_DIRECT, $flags = AMQP_DURABLE);

    /**
     * @param bool $name_only
     *
     * @return \AMQPExchange[]|string[]
     */
    public function getExchanges($name_only = true);

    /**
     * @param string $name
     * @param int    $flags Optionally AMQP_IFUNUSED can be specified to indicate the exchange should not be deleted until no clients are connected to it.
     *
     * @return static
     */
    public function deleteExchange($name, $flags = AMQP_NOPARAM);

    /**
     * @param string $name
     * @param int    $flags
     *
     * @return \AMQPQueue
     */
    public function declareQueue($name, $flags = AMQP_DURABLE);

    /**
     * @param bool $name_only
     *
     * @return \AMQPQueue[]|string[]
     */
    public function getQueues($name_only = true);

    /**
     * @param string $queue
     * @param string $exchange
     * @param string $binding_key
     *
     * @return static
     */
    public function bindQueue($queue, $exchange, $binding_key = '');

    /**
     *  Purge the contents of a queue
     *
     * @param string $name
     *
     * @return static
     */
    public function purgeQueue($name);

    /**
     * @param string $name
     *
     * @return static
     */
    public function deleteQueue($name);

    /**
     * @param string $message
     * @param string $exchange
     * @param string $routing_key
     * @param int    $flags One or more of AMQP_MANDATORY and AMQP_IMMEDIATE
     * @param array  $attributes
     *
     * @return static
     */
    public function publishMessage($message, $exchange, $routing_key = '', $flags = AMQP_NOPARAM, $attributes = []);

    /**
     * @param array|\JsonSerializable $message
     * @param string                  $exchange
     * @param string                  $routing_key
     * @param int                     $flags One or more of AMQP_MANDATORY and AMQP_IMMEDIATE
     * @param array                   $attributes
     *
     * @return static
     */
    public function publishJsonMessage($message, $exchange, $routing_key = '', $flags = AMQP_NOPARAM, $attributes = []);

    /**
     * @param string $queue
     * @param bool   $auto_ack
     *
     * @return false|\ManaPHP\Amqp\Message
     */
    public function getMessage($queue, $auto_ack = false);

    /**
     * @param string $queue
     * @param bool   $auto_ack
     *
     * @return false|array
     */
    public function getJsonMessage($queue, $auto_ack = false);

    /**
     * @param \ManaPHP\Amqp\Message|array $message
     * @param bool                        $multiple
     *
     * @return static
     */
    public function ackMessage($message, $multiple = false);

    /**
     * @param \ManaPHP\Amqp\Message|array $message
     * @param bool                        $multiple
     *
     * @return static
     */
    public function nackMessage($message, $multiple = false);

    /**
     * @param string   $queue
     * @param callable $callback
     * @param int      $flags
     *
     * @return void
     */
    public function consumeMessages($queue, $callback, $flags = AMQP_NOPARAM);
}