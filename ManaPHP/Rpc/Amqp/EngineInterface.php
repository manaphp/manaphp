<?php

namespace ManaPHP\Rpc\Amqp;

interface EngineInterface
{
    /**
     * @param string $exchange
     * @param string $routing_key
     * @param string $body
     * @param array  $properties
     * @param array  $options
     *
     * @return mixed
     */
    public function call($exchange, $routing_key, $body, $properties, $options);
}