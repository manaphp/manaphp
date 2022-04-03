<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Component;
use ManaPHP\Event\Emitter;
use ManaPHP\Event\EmitterInterface;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Ws\Client\EngineInterface;
use ManaPHP\Ws\Client\Message;
use Throwable;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Client extends Component implements ClientInterface
{
    protected string $endpoint;
    protected ?string $proxy;
    protected float $timeout = 3.0;
    protected ?string $protocol;
    protected bool $masking = true;
    protected ?string $origin;
    protected string $user_agent = 'manaphp/client';
    protected EngineInterface $engine;
    protected int $pool_size = 4;
    protected EmitterInterface $emitter;

    public function __construct(string $endpoint, ?string $proxy = null, float $timeout = 3.0, ?string $protocol = null,
        bool $masking = true, ?string $origin = null, string $user_agent = 'manaphp/client', int $pool_size = 4
    ) {
        $this->endpoint = $endpoint;
        $this->proxy = $proxy;
        $this->timeout = $timeout;
        $this->protocol = $protocol;
        $this->masking = $masking;
        $this->origin = $origin;
        $this->user_agent = $user_agent;
        $this->pool_size = $pool_size;

        $parameters = compact(
            'endpoint', 'proxy', 'timeout', 'protocol', 'masking', 'origin', 'user_agent', 'pool_size'
        );
        $parameters['owner'] = $this;

        $sample = $this->container->make('ManaPHP\Ws\Client\Engine', $parameters);
        $this->poolManager->add($this, $sample, $this->pool_size);

        $this->emitter = new Emitter();

    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    public function on(string $event, callable $handler): static
    {
        $this->emitter->on($event, $handler);

        return $this;
    }

    public function emit(string $event, mixed $data = null): mixed
    {
        return $this->emitter->emit($event, $data);
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;

        $size = $this->poolManager->size($this);

        $engines = [];
        for ($i = 0; $i < $size; $i++) {
            /** @var \ManaPHP\Ws\Client\EngineInterface $engine */
            $engine = $this->poolManager->pop($this);
            $engine->setEndpoint($endpoint);
            $engines[] = $engine;
        }

        foreach ($engines as $engine) {
            $this->poolManager->push($this, $engine);
        }

        return $this;
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    public function request(string $message, ?float $timeout = null): Message
    {
        $end_time = microtime(true) + ($timeout ?? $this->timeout);

        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            $engine->send(Message::TEXT_FRAME, $message, max($end_time - microtime(true), 0.01));
            return $engine->recv(max($end_time - microtime(true), 0.01));
        } catch (Throwable $throwable) {
            $engine->close();
            $engine->send(Message::TEXT_FRAME, $message, max($end_time - microtime(true), 0.01));
            return $engine->recv(max($end_time - microtime(true), 0.01));
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }

    public function subscribe(callable $handler, int $keepalive = 60): void
    {
        $last_time = null;

        $engine = $this->poolManager->pop($this, $this->timeout);

        try {
            do {
                while (true) {
                    if ($engine->isRecvReady($keepalive > 0 ? $keepalive : 1)) {
                        break;
                    }

                    if ($keepalive > 0 && microtime(true) - $last_time > $keepalive) {
                        $engine->send(Message::PING_FRAME, '', $this->timeout);
                        $last_time = microtime(true);
                    }
                }

                $message = $engine->recv($this->timeout);
                $last_time = microtime(true);
                $op_code = $message->op_code;

                $r = null;
                if ($op_code === Message::TEXT_FRAME || $op_code === Message::BINARY_FRAME) {
                    $r = $handler($message->payload, $this);
                } elseif ($op_code === Message::CLOSE_FRAME) {
                    $r = false;
                } elseif ($op_code === Message::PING_FRAME) {
                    $engine->send(Message::PONG_FRAME, $message->payload, $this->timeout);
                }
            } while ($r !== false);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }
}