<?php

namespace ManaPHP\Ws;

use ManaPHP\Component;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Ws\Client\Message;
use Throwable;

class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $_endpoint;

    /**
     * @var string
     */
    protected $_proxy;

    /**
     * @var float
     */
    protected $_timeout = 3.0;

    /**
     * @var string
     */
    protected $_protocol;

    /**
     * @var bool
     */
    protected $_masking = true;

    /**
     * @var string
     */
    protected $_origin;

    /**
     * @var string
     */
    protected $_user_agent = 'manaphp/client';

    /**
     * @var \ManaPHP\Ws\Client\EngineInterface
     */
    protected $_engine;

    /**
     * @var int
     */
    protected $_pool_size = 4;

    /**
     * Client constructor.
     *
     * @param array $options
     */
    public function __construct($options)
    {
        $this->_endpoint = $options['endpoint'];

        if (isset($options['proxy'])) {
            $this->_proxy = $options['proxy'];
        }

        if (isset($options['timeout'])) {
            $this->_timeout = $options['timeout'];
        }

        if (isset($options['protocol'])) {
            $this->_protocol = $options['protocol'];
        }

        if (isset($options['masking'])) {
            $this->_masking = (bool)$options['masking'];
        }

        if (isset($options['origin'])) {
            $this->_origin = $options['origin'];
        }

        if (isset($options['user_agent'])) {
            $this->_user_agent = $options['user_agent'];
        }

        if (isset($options['pool_size'])) {
            $this->_pool_size = (int)$options['pool_size'];
        }

        $options['owner'] = $this;

        $options['class'] = 'ManaPHP\Ws\Client\Engine';

        $this->poolManager->add($this, $options, $this->_pool_size);
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->_endpoint;
    }

    /**
     * @param string $endpoint
     *
     * @return static
     */
    public function setEndpoint($endpoint)
    {
        $this->_endpoint = $endpoint;

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
        $end_time = microtime(true) + ($timeout ?? $this->_timeout);

        $engine = $this->poolManager->pop($this, $this->_timeout);

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

        $engine = $this->poolManager->pop($this, $this->_timeout);

        try {
            do {
                while (true) {
                    if ($engine->isRecvReady($keepalive > 0 ? $keepalive : 1)) {
                        break;
                    }

                    if ($keepalive > 0 && microtime(true) - $last_time > $keepalive) {
                        $engine->send(Message::PING_FRAME, '', $this->_timeout);
                        $last_time = microtime(true);
                    }
                }

                $message = $engine->recv($this->_timeout);
                $last_time = microtime(true);
                $op_code = $message->op_code;

                $r = null;
                if ($op_code === Message::TEXT_FRAME || $op_code === Message::BINARY_FRAME) {
                    $r = $handler($message->payload, $this);
                } elseif ($op_code === Message::CLOSE_FRAME) {
                    $r = false;
                } elseif ($op_code === Message::PING_FRAME) {
                    $engine->send(Message::PONG_FRAME, $message->payload, $this->_timeout);
                }
            } while ($r !== false);
        } finally {
            $this->poolManager->push($this, $engine);
        }
    }
}