<?php

namespace ManaPHP\Amqp\Engine;

use ManaPHP\Amqp\Bind;
use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Amqp\EngineInterface;
use ManaPHP\Amqp\Exchange;
use ManaPHP\Amqp\MessageInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use ManaPHP\Amqp\Queue;
use ManaPHP\Amqp\Engine\Php\Message as PhpMessage;

class Php extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var AMQPStreamConnection
     */
    protected $connection;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var array
     */
    protected $exchanges;

    /**
     * @var array
     */
    protected $queues;

    /**
     * @param string $uri
     */
    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return AMQPChannel
     */
    protected function getChannel()
    {
        if ($this->channel === null) {
            $parts = parse_url($this->uri);
            $scheme = $parts['scheme'];
            if ($scheme !== 'amqp') {
                throw new MisuseException('only support ampq scheme');
            }
            $host = $parts['host'];
            $port = isset($parts['port']) ? (int)$parts['port'] : 5672;
            $user = $parts['user'] ?? 'guest';
            $password = $parts['pass'] ?? 'guest';
            $vhost = $parts['path'] ?? '/';
            $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);

            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    /**
     * @param AMQPChannel $channel
     * @param Exchange    $exchange
     */
    protected function exchangeDeclareInternal($channel, $exchange)
    {
        $name = $exchange->name;
        if (isset($this->exchanges[$name])) {
            return;
        }

        $features = $exchange->features;
        $channel->exchange_declare(
            $name, $exchange->type,
            $features['passive'], $features['durable'], $features['auto_delete'],
            $features['internal'], $features['nowait'], $features['arguments']
        );
        $this->exchanges[$name] = 1;
    }

    /**
     * @param Exchange $exchange
     *
     * @return void
     */
    public function exchangeDeclare($exchange)
    {
        $this->exchangeDeclareInternal($this->getChannel(), $exchange);
    }

    /**
     * @param AMQPChannel $channel
     * @param Queue       $queue
     *
     * @return void
     */
    protected function queueDeclareInternal($channel, $queue)
    {
        $name = $queue->name;
        if (isset($this->queues[$queue->name])) {
            return;
        }

        $features = $queue->features;
        $channel->queue_declare(
            $name,
            $features['passive'], $features['durable'],
            $features['exclusive'], $features['auto_delete'],
            $features['nowait'], $features['arguments']
        );
        $this->queues[$name] = 1;
    }

    /**
     * @param Queue $queue
     *
     * @return void
     */
    public function queueDeclare($queue)
    {
        $this->queueDeclareInternal($this->getChannel(), $queue);
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
        $channel = $this->getChannel();
        $channel->queue_delete($queue, $if_unused, $if_empty, $nowait);
    }

    /**
     * @param Bind $bind
     *
     * @return void
     */
    public function queueBind($bind)
    {
        $queue = $bind->queue;
        $exchange = $bind->exchange;

        $channel = $this->getChannel();
        if (is_object($queue)) {
            $this->queueDeclareInternal($channel, $queue);
        }

        if (is_object($exchange)) {
            $this->exchangeDeclareInternal($channel, $exchange);
        }

        $channel->queue_bind(
            is_string($queue) ? $queue : $queue->name,
            is_string($exchange) ? $exchange : $exchange->name,
            $bind->binding_key, false, $bind->arguments
        );
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
        $channel = $this->getChannel();
        $channel->exchange_delete($exchange, $if_unused, $nowait);
    }

    /**
     * @param string|Exchange $exchange
     * @param string|Queue    $routingKey
     * @param string|array    $body
     * @param array           $properties
     * @param bool            $mandatory
     *
     * @return void
     */
    public function basicPublish($exchange, $routingKey, $body, $properties, $mandatory)
    {
        $channel = $this->getChannel();
        if (is_object($exchange)) {
            $this->exchangeDeclareInternal($channel, $exchange);
        }

        if (is_object($routingKey)) {
            $this->queueDeclareInternal($channel, $routingKey);
            $routingKey = $routingKey->name;
        }

        $message = new AMQPMessage($body, $properties);
        $exchangeName = is_string($exchange) ? $exchange : $exchange->name;
        $channel->basic_publish($message, $exchangeName, $routingKey, $mandatory);
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
    public function basicConsume($queue, $callback, $no_ack, $exclusive, $tag)
    {
        $channel = $this->getChannel();

        if (is_object($queue)) {
            $this->queueDeclareInternal($channel, $queue);
        }

        $queueName = is_string($queue) ? $queue : $queue->name;
        return $channel->basic_consume($queueName, $tag, false, $no_ack, $exclusive, false, $callback);
    }

    /**
     * @param int $prefetchSize
     * @param int $prefetchCount
     *
     * @return void
     */
    public function wait($prefetchSize, $prefetchCount)
    {
        $channel = $this->getChannel();
        $channel->basic_qos($prefetchSize, $prefetchCount, true);
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    /**
     * @param mixed  $message
     * @param string $queue
     *
     * @return MessageInterface
     */
    public function wrapMessage($message, $queue)
    {
        return new PhpMessage($message, $queue);
    }
}