<?php
declare(strict_types=1);

namespace ManaPHP\Amqp\Engine;

use ManaPHP\Amqp\Binding;
use ManaPHP\Amqp\ChannelException;
use ManaPHP\Amqp\Engine\Php\Message as PhpMessage;
use ManaPHP\Amqp\EngineInterface;
use ManaPHP\Amqp\Exchange;
use ManaPHP\Amqp\MessageInterface;
use ManaPHP\Amqp\Queue;
use ManaPHP\Exception\MisuseException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;

class Php implements EngineInterface
{
    #[Autowired] protected string $uri;

    protected AMQPStreamConnection $connection;
    protected ?AMQPChannel $channel = null;
    protected array $exchanges;
    protected array $queues;

    protected function getChannel(): AMQPChannel
    {
        if ($this->channel !== null && !$this->channel->is_open()) {
            $this->channel = null;
            $this->exchanges = [];
            $this->queues = [];
        }

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

    protected function exchangeDeclareInternal(AMQPChannel $channel, Exchange $exchange): void
    {
        $name = $exchange->name;
        if (isset($this->exchanges[$name])) {
            return;
        }

        $features = $exchange->features;
        try {
            $channel->exchange_declare(
                $name, $exchange->type,
                $features['passive'], $features['durable'], $features['auto_delete'],
                $features['internal'], $features['nowait'], $features['arguments']
            );
        } catch (AMQPProtocolChannelException $exception) {
            throw new ChannelException($exception);
        }

        $this->exchanges[$name] = 1;
    }

    public function exchangeDeclare(Exchange $exchange): void
    {
        $this->exchangeDeclareInternal($this->getChannel(), $exchange);
    }

    public function exchangeDelete(string $exchange, bool $if_unused = false, bool $nowait = false): void
    {
        unset($this->exchanges[$exchange]);

        $channel = $this->getChannel();
        $channel->exchange_delete($exchange, $if_unused, $nowait);
    }

    protected function queueDeclareInternal(AMQPChannel $channel, Queue $queue): void
    {
        $name = $queue->name;
        if (isset($this->queues[$queue->name])) {
            return;
        }

        $features = $queue->features;
        try {
            $channel->queue_declare(
                $name,
                $features['passive'], $features['durable'],
                $features['exclusive'], $features['auto_delete'],
                $features['nowait'], $features['arguments']
            );
        } catch (AMQPProtocolChannelException $exception) {
            throw new ChannelException($exception);
        }
        $this->queues[$name] = 1;
    }

    public function queueDeclare(Queue $queue): void
    {
        $this->queueDeclareInternal($this->getChannel(), $queue);
    }

    public function queueDelete(string $queue, bool $if_unused = false, bool $if_empty = false, bool $nowait = false
    ): void {
        unset($this->queues[$queue]);

        $channel = $this->getChannel();
        $channel->queue_delete($queue, $if_unused, $if_empty, $nowait);
    }

    public function queueBind(Binding $binding): void
    {
        $queue = $binding->queue;
        $exchange = $binding->exchange;

        $channel = $this->getChannel();
        if (is_object($queue)) {
            $this->queueDeclareInternal($channel, $queue);
        }

        if (is_object($exchange)) {
            $this->exchangeDeclareInternal($channel, $exchange);
        }

        try {
            $channel->queue_bind(
                is_string($queue) ? $queue : $queue->name,
                is_string($exchange) ? $exchange : $exchange->name,
                $binding->binding_key, false, $binding->arguments
            );
        } catch (AMQPProtocolChannelException $exception) {
            throw new ChannelException($exception);
        }
    }

    public function queueUnbind(Binding $binding): void
    {
        $queue = $binding->queue;
        $exchange = $binding->exchange;

        $channel = $this->getChannel();
        try {
            $channel->queue_unbind(
                is_string($queue) ? $queue : $queue->name,
                is_string($exchange) ? $exchange : $exchange->name,
                $binding->binding_key, $binding->arguments
            );
        } catch (AMQPProtocolChannelException $exception) {
            throw new ChannelException($exception);
        }

    }

    public function basicPublish(string|Exchange $exchange, string|Queue $routing_key, string|array $body,
        array $properties, bool $mandatory
    ): void {
        $channel = $this->getChannel();
        if (is_object($exchange)) {
            $this->exchangeDeclareInternal($channel, $exchange);
        }

        if (is_object($routing_key)) {
            $this->queueDeclareInternal($channel, $routing_key);
            $routing_key = $routing_key->name;
        }

        $message = new AMQPMessage($body, $properties);
        $exchangeName = is_string($exchange) ? $exchange : $exchange->name;
        $channel->basic_publish($message, $exchangeName, $routing_key, $mandatory);
    }

    public function basicConsume(string|Queue $queue, callable $callback, bool $no_ack, bool $exclusive, string $tag
    ): string {
        $channel = $this->getChannel();

        if (is_object($queue)) {
            $this->queueDeclareInternal($channel, $queue);
        }

        $queueName = is_string($queue) ? $queue : $queue->name;
        return $channel->basic_consume($queueName, $tag, false, $no_ack, $exclusive, false, $callback);
    }

    public function wait(int $prefetchSize, int $prefetchCount): void
    {
        $channel = $this->getChannel();
        $channel->basic_qos($prefetchSize, $prefetchCount, true);
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function wrapMessage(mixed $message, string $queue): MessageInterface
    {
        return new PhpMessage($message, $queue);
    }

}