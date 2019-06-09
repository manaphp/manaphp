<?php
namespace App\Processes;

use ManaPHP\Swoole\Process;
use Redis;

/**
 * Class SubscriberProcess
 * @package App\Processes
 * @property-read \ManaPHP\WebSocket\ServerInterface $wsServer
 */
class SubscriberProcess extends Process
{
    /**
     * @var string
     */
    protected $_prefix = 'ws:';

    /**
     * @var array
     */
    protected $_endpoints = null;

    public function __construct($options = [])
    {
        parent::__construct();

        if (isset($options['endpoints'])) {
            $this->_endpoints = $options['endpoints'];
        }
    }

    public function dispatch($channel, $data)
    {
        if (($pos = strrpos($channel, ':')) !== false) {
            $type = substr($channel, $pos + 1);
            $pos2 = strrpos($pos, $channel, $pos - 1);
            $endpoint = substr($channel, $pos2, $pos - $pos2);
            $method = 'channel' . ucfirst($type);

            if (method_exists($this, $method)) {
                if (($pos = strpos($data, ':', $data)) === false) {
                    $this->logger->warn(['unpack message: failed :message', 'type' => $type, 'message' => $data]);
                } else {
                    $this->$method($endpoint, substr($data, 0, $pos), substr($data, $pos + 1));
                }
            } else {
                $this->logger->warn(['unknown `:type` type message: :message', 'type' => $type, 'message' => $data]);
            }
        }
    }

    /**
     * @param string $endpoint
     * @param string $target
     * @param string $message
     *
     * @return void
     */
    public function channelFd($endpoint, $target, $message)
    {
        foreach (explode(',', $target) as $fd) {
            $this->wsServer->push($fd, $message);
        }
    }

    /**
     * @param string $endpoint
     * @param string $target
     * @param string $message
     *
     * @return void
     */
    public function channelUser($endpoint, $target, $message)
    {
        foreach (explode(',', $target) as $user) {
            $this->wsServer->pushToUser($user, $message);
        }
    }

    /**
     * @param string $endpoint
     * @param string $target
     * @param string $message
     *
     * @return void
     */
    public function channelRoom($endpoint, $target, $message)
    {
        $rooms = [];
        $excluded = [];
        foreach (explode(',', $target) as $part) {
            if ($part[0] === '-') {
                $excluded[] = substr($part, 1);
            } else {
                $rooms[] = $part;
            }
        }

        foreach ($rooms as $room) {
            $key = $this->_prefix . 'room:' . $room;
            $type = $this->redis->type($key);
            if ($type === Redis::REDIS_STRING) {
                $users = explode(',', $this->redis->get($key));
            } elseif ($type === Redis::REDIS_SET) {
                $users = $this->redis->sMembers($key);
            } else {
                $users = [];
                $this->logger->warn(['`:room` room is not exists', 'room' => $room]);
            }

            $this->wsServer->pushToUsers($excluded ? array_values(array_diff($users, $excluded)) : $users, $message);
        }
    }

    /**
     * @param string $endpoint
     * @param string $target
     * @param string $message
     *
     * @return void
     */
    public function channelAll($endpoint, $target, $message)
    {
        $this->wsServer->broadcast($message);
    }

    public function run()
    {
        $channels = [];
        if ($this->_endpoints) {
            foreach ($this->_endpoints as $endpoint) {
                $channels[] = $this->_prefix . 'channel:' . $endpoint . ':*';
            }
        } else {
            $channels[] = $this->_prefix . 'channel:*';
        }

        $this->redis->psubscribe($channels, function ($redis, $pattern, $channel, $data) {
            $this->dispatch($channel, $data);
        });
    }
}