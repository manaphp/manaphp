<?php

namespace ManaPHP\Amqp;

interface ClientInterface
{
    /**
     * @param Exchange $exchange
     *
     * @return void
     */
    public function exchangeDeclare($exchange);

    /**
     * @param string $exchange
     * @param bool   $if_unused
     * @param bool   $nowait
     *
     * @return void
     */
    public function exchangeDelete($exchange, $if_unused = false, $nowait = false);

    /**
     * @param Queue $queue
     *
     * @return void
     */
    public function queueDeclare($queue);

    /**
     * @param string $queue
     * @param bool   $if_unused
     * @param bool   $if_empty
     * @param bool   $nowait
     *
     * @return void
     */
    public function queueDelete($queue, $if_unused = false, $if_empty = false, $nowait = false);

    /**
     * @param Bind $bind
     *
     * @return void
     */
    public function queueBind($bind);

    /**
     * @param string|Exchange $exchange
     * @param string|Queue    $routing_key
     * @param string|array    $body
     * @param array           $properties
     * @param bool            $mandatory
     *
     * @return void
     */
    public function basicPublish($exchange, $routing_key, $body, $properties = [], $mandatory = false);

    /**
     * @param string|Queue $queue
     * @param callable     $callback
     * @param bool         $no_ack https://www.rabbitmq.com/amqp-0-9-1-reference.html#domain.no-ack
     * @param bool         $exclusive
     * @param string       $tag
     *
     * @return string
     */
    public function basicConsume($queue, $callback, $no_ack = false, $exclusive = false, $tag = '');

    /**
     * @param int $prefetch_size
     * @param int $prefetch_count
     *
     * @return void
     */
    public function startConsume($prefetch_size = 0, $prefetch_count = 0);
}
