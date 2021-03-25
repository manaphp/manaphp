<?php

namespace ManaPHP\Amqp;

interface MessageInterface
{
    /**
     * @return string
     */
    public function getQueue();

    /**
     * @return array
     */
    public function getProperties();

    /**
     * @return string
     */
    public function getBody();

    /**
     * @return array
     */
    public function getJsonBody();

    /**
     * @return string
     */
    public function getExchange();

    /**
     * @return string
     */
    public function getRoutingKey();

    /**
     * @return int
     */
    public function getDeliveryTag();

    /**
     * @return bool
     */
    public function isRedelivered();
}