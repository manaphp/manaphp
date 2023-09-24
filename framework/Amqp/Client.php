<?php
declare(strict_types=1);

namespace ManaPHP\Amqp;

use ManaPHP\Amqp\Client\Event\AmqpClientConsumed;
use ManaPHP\Amqp\Client\Event\AmqpClientConsuming;
use ManaPHP\Amqp\Client\Event\AmqpClientPublish;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Pooling\PoolManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class Client implements ClientInterface
{
    #[Inject] protected EventDispatcherInterface $eventDispatcher;
    #[Inject] protected PoolManagerInterface $poolManager;

    #[Value] protected string $uri;

    protected int $pool_size = 4;
    protected int $timeout = 3;
    protected ?EngineInterface $engine = null;

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct()
    {
        if (preg_match('#pool_size=(\d+)#', $this->uri, $match)) {
            $this->pool_size = (int)$match[1];
        }

        $this->poolManager->add($this, [EngineInterface::class, ['uri' => $this->uri]], $this->pool_size);
    }

    public function exchangeDeclare(Exchange $exchange): void
    {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->exchangeDeclare($exchange);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    public function exchangeDelete(string $exchange, bool $if_unused = false, bool $nowait = false): void
    {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->exchangeDelete($exchange, $if_unused, $nowait);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    public function queueDeclare(Queue $queue): void
    {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->queueDeclare($queue);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    public function queueDelete(string $queue, bool $if_unused = false, bool $if_empty = false, bool $nowait = false
    ): void {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->queueDelete($queue, $if_unused, $if_empty, $nowait);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    public function queueBind(Binding $binding): void
    {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->queueBind($binding);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    public function queueUnbind(Binding $binding): void
    {
        /** @var \ManaPHP\Amqp\EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->queueUnbind($binding);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    public function basicPublish(string|Exchange $exchange, string|Queue $routing_key, string|array $body,
        array $properties = [], bool $mandatory = false
    ): void {
        if (!is_string($body)) {
            $body = json_stringify($body);
            $properties['content_type'] ??= 'application/json';
        }

        $this->eventDispatcher->dispatch(
            new AmqpClientPublish($this, $exchange, $routing_key, $body, $properties, $mandatory)
        );

        /** @var EngineInterface $engine */
        $engine = $this->poolManager->pop($this, $this->timeout);
        try {
            $engine->basicPublish($exchange, $routing_key, $body, $properties, $mandatory);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    public function basicConsume(string|Queue $queue, callable $callback, bool $no_ack = false, bool $exclusive = false,
        string $tag = ''
    ): string {
        if ($this->engine === null) {
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $this->engine = $this->poolManager->pop($this, $this->timeout);
        }

        $wrapper = function ($rawMessage) use ($callback, $queue) {
            $message = $this->engine->wrapMessage($rawMessage, $queue);
            $this->eventDispatcher->dispatch(new AmqpClientConsuming($this, $queue, $message));
            $return = $callback($message);
            $this->eventDispatcher->dispatch(new AmqpClientConsumed($this, $queue, $message, $return));
        };

        return $this->engine->basicConsume($queue, $wrapper, $no_ack, $exclusive, $tag);
    }

    public function startConsume(int $prefetch_size = 0, int $prefetch_count = 0): void
    {
        if ($this->engine === null) {
            throw new MisuseException('none consume');
        }

        $this->engine->wait($prefetch_size, $prefetch_count);
    }
}