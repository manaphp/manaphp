<?php

namespace ManaPHP\Amqp;

class Binding
{
    /**
     * @var string|Queue
     */
    public $queue;

    /**
     * @var string|Exchange
     */
    public $exchange;

    /**
     * @var string
     */
    public $binding_key;

    /**
     * @var array
     */
    public $arguments;

    /**
     * @param string|Queue    $queue
     * @param string|Exchange $exchange
     * @param string          $binding_key
     * @param array           $arguments
     */
    public function __construct($queue, $exchange, $binding_key, $arguments = [])
    {
        $this->queue = $queue;
        $this->exchange = $exchange;
        $this->binding_key = $binding_key;
        $this->arguments = $arguments;
    }
}