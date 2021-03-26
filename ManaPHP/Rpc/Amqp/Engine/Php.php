<?php

namespace ManaPHP\Rpc\Amqp\Engine;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Rpc\Amqp\EngineInterface;
use ManaPHP\Rpc\Amqp\TimeoutException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Swoole\Coroutine;

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
     * @var string
     */
    protected $reply_to;

    /**
     * @var array
     */
    protected $callings;

    /**
     * @var array
     */
    protected $replies;

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
        if ($this->channel !== null && !$this->channel->is_open()) {
            $this->channel = null;
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

            $this->channel = $channel = $this->connection->channel();

            list($this->reply_to) = $channel->queue_declare('', false, false, true);

            if (MANAPHP_COROUTINE_ENABLED) {
                $channel->basic_qos(0, 1, false);
                $channel->basic_consume(
                    $this->reply_to, '', false, true, false, false, function (AMQPMessage $message) {
                    $cid = $message->get('correlation_id');

                    $this->replies[$cid] = $message->body;
                    Coroutine::resume($cid);
                }
                );

                Coroutine::create(
                    function () {
                        while (true) {
                            $current = microtime(true);
                            foreach ($this->callings as $cid => $end) {
                                if ($end > $current) {
                                    $this->replies[$cid] = new TimeoutException('timeout');
                                    Coroutine::resume($cid);
                                }
                            }
                            usleep(100000);
                        }
                    }
                );
            } else {
                $channel->basic_qos(0, 1, false);
                $channel->basic_consume(
                    $this->reply_to, '', false, true, false, false, function (AMQPMessage $message) {
                    $this->replies[] = $message->body;
                }
                );
            }
        }

        return $this->channel;
    }

    /**
     * @param string $exchange
     * @param string $routing_key
     * @param string $body
     * @param array  $properties
     * @param array  $options
     *
     * @return mixed
     */
    public function call($exchange, $routing_key, $body, $properties, $options)
    {
        $channel = $this->getChannel();

        $properties['reply_to'] = $this->reply_to;

        if (MANAPHP_COROUTINE_ENABLED) {
            $cid = Coroutine::getCid();

            $this->callings[$cid] = microtime(true) + 1;

            $properties['correlation_id'] = $cid;
            $message = new AMQPMessage($body, $properties);
            $channel->basic_publish($message, $exchange, $routing_key);
            Coroutine::suspend();

            $reply = $this->replies[$cid];
            unset($this->replies[$cid], $this->callings[$cid]);

            if ($reply instanceof TimeoutException) {
                throw $reply;
            } else {
                return $reply;
            }
        } else {
            $message = new AMQPMessage($body, $properties);

            $channel->basic_publish($message, $exchange, $routing_key);
            $channel->wait();
            if ($this->replies) {
                $reply = $this->replies[0];
                $this->replies = null;
                return $reply;
            } else {
                throw new TimeoutException('timeout');
            }
        }
    }
}