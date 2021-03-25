<?php

namespace ManaPHP\Amqp;

interface EngineInterface
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
    public function queueDelete($queue, $if_unused, $if_empty, $nowait);

    /**
     * @param Bind $bind
     *
     * @return void
     */
    public function queueBind($bind);

    /**
     * @param string|Exchange $exchange
     * @param string|Queue    $routingKey
     * @param string|array    $body
     * @param array           $properties
     * @param bool            $mandatory
     *
     * @return void
     */
    public function basicPublish($exchange, $routingKey, $body, $properties, $mandatory);

    /**
     * @param string|Queue $queue
     * @param callable     $callback
     * @param bool         $no_ack
     * @param bool         $exclusive
     * @param string       $tag
     *
     * @return string
     */
    public function basicConsume($queue, $callback, $no_ack, $exclusive, $tag);

    /**
     * @param int $prefetchSize
     * @param int $prefetchCount
     *
     * @return void
     */
    public function wait($prefetchSize, $prefetchCount);

    /**
     * @param mixed  $message
     * @param string $queue
     *
     * @return MessageInterface
     */
    public function wrapMessage($message, $queue);
}