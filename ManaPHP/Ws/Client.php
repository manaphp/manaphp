<?php

namespace ManaPHP\Ws;

use ManaPHP\Component;
use ManaPHP\Event\Emitter;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Ws\Client\Message;
use Throwable;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $proxy;

    /**
     * @var float
     */
    protected $timeout = 3.0;

    /**
     * @var string
     */
    protected $protocol;

    /**
     * @var bool
     */
    protected $masking = true;

    /**
     * @var string
     */
    protected $origin;

    /**
     * @var string
     */
    protected $user_agent = 'manaphp/client';

    /**
     * @var \ManaPHP\Ws\Client\EngineInterface
     */
    protected $engine;

    /**
     * @var int
     */
    protected $pool_size = 4;

    /**
     * @var \ManaPHP\Event\EmitterInterface
     */
    protected $emitter;

    /**
     * @param array $options
     */
    public function __construct($options)
    {
        $this->endpoint = $options['endpoint'];

        if (isset($options['proxy'])) {
            $this->proxy = $options['proxy'];
        }

        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }

        if (isset($options['protocol'])) {
            $this->protocol = $options['protocol'];
        }

        if (isset($options['masking'])) {
            $this->masking = (bool)$options['masking'];
        }

        if (isset($options['origin'])) {
            $this->origin = $options['origin'];
        }

        if (isset($options['user_agent'])) {
            $this->user_agent = $options['user_agent'];
        }

        if (isset($options['pool_size'])) {
            $this->pool_size = (int)$options['pool_size'];
        }

        $options['owner'] = $this;

        $sample = $this->container->make('ManaPHP\Ws\Client\Engine', [$options]);
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

    public function on($event, $handler)
    {
        $this->emitter->on($event, $handler);

        return $this;
    }

    public function emit($event, $data = null)
    {
        return $this->emitter->emit($event, $data);
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param string $endpoint
     *
     * @return static
     */
    public function setEndpoint($endpoint)
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

    /**
     * @param string $message
     * @param float  $timeout
     *
     * @return \ManaPHP\Ws\Client\Message
     */
    public function request($message, $timeout = null)
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

    /**
     * @param callable $handler
     * @param int      $keepalive
     *
     * @return void
     */
    public function subscribe($handler, $keepalive = 60)
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