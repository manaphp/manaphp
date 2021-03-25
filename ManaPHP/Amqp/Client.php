<?php

namespace ManaPHP\Amqp;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var int
     */
    protected $pool_size = 4;

    /**
     * @var int
     */
    protected $timeout = 3;

    /**
     * @var EngineInterface
     */
    protected $engine;

    /**
     * @param string $uri
     */
    public function __construct($uri)
    {
        $this->uri = $uri;

        if (preg_match('#pool_size=(\d+)#', $uri, $match)) {
            $this->pool_size = (int)$match[1];
        }

        $definition = ['class' => 'ManaPHP\Amqp\Engine\Php', $uri];

        $this->poolManager->add($this, $definition, $this->pool_size);
    }

    /**
     * @param Exchange $exchange
     *
     * @return void
     */
    public function exchangeDeclare($exchange)
    {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->exchangeDeclare($exchange);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    /**
     * @param string $exchange
     * @param bool   $if_unused
     * @param bool   $nowait
     *
     * @return void
     */
    public function exchangeDelete($exchange, $if_unused = false, $nowait = false)
    {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->exchangeDelete($exchange, $if_unused, $nowait);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    /**
     * @param Queue $queue
     *
     * @return void
     */
    public function queueDeclare($queue)
    {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->queueDeclare($queue);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    /**
     * @param string $queue
     * @param bool   $if_unused
     * @param bool   $if_empty
     * @param bool   $nowait
     *
     * @return void
     */
    public function queueDelete($queue, $if_unused = false, $if_empty = false, $nowait = false)
    {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->queueDelete($queue, $if_unused, $if_empty, $nowait);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    /**
     * @param Bind $bind
     *
     * @return void
     */
    public function queueBind($bind)
    {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->queueBind($bind);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    /**
     * @param string|Exchange $exchange
     * @param string|Queue    $routing_key
     * @param string|array    $body
     * @param array           $properties
     * @param bool            $mandatory
     *
     * @return void
     */
    public function basicPublish($exchange, $routing_key, $body, $properties = [], $mandatory = false)
    {
        if (!is_string($body)) {
            $body = json_stringify($body);
            if (!isset($properties['content_type'])) {
                $properties['content_type'] = 'application/json';
            }
        }

        $this->fireEvent('amqpClient:publish', compact('exchange', 'routing_key', 'body', 'properties', 'mandatory'));

        /** @var EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);
        try {
            $engine->basicPublish($exchange, $routing_key, $body, $properties, $mandatory);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    /**
     * @param string|Queue $queue
     * @param callable     $callback
     * @param bool         $no_ack
     * @param bool         $exclusive
     * @param string       $tag
     *
     * @return string
     */
    public function basicConsume($queue, $callback, $no_ack = false, $exclusive = false, $tag = '')
    {
        if ($this->engine === null) {
            $this->engine = $this->poolManager->pop($this, $this->timeout);
        }

        $wrapper = function ($rawMessage) use ($callback, $queue) {
            $message = $this->engine->wrapMessage($rawMessage, $queue);
            $this->fireEvent('amqpClient:consuming', $message);
            $return = $callback($message);
            $this->fireEvent('amqpClient:consumed', compact('message', 'return'));
        };

        return $this->engine->basicConsume($queue, $wrapper, $no_ack, $exclusive, $tag);
    }

    /**
     * @param int $prefetch_size
     * @param int $prefetch_count
     *
     * @return void
     */
    public function startConsume($prefetch_size = 0, $prefetch_count = 0)
    {
        if ($this->engine === null) {
            throw new MisuseException('none consume');
        }

        $this->engine->wait($prefetch_size, $prefetch_count);
    }
}