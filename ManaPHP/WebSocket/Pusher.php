<?php
namespace ManaPHP\WebSocket;

use ManaPHP\Component;
use ManaPHP\Exception\MissingFieldException;

class Pusher extends Component implements PusherInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'ws:';

    /**
     * @var string
     */
    protected $_endpoint;

    /**
     * Pusher constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }

        if (isset($options['endpoint'])) {
            $this->_endpoint = $options['endpoint'];
        }
    }

    /**
     * @param string       $channel
     * @param string|array $data
     *
     * @return void
     */
    protected function _push($channel, $data)
    {
        $this->redis->publish($this->_prefix . 'channel:' . $channel, is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param string|int|int[]         $fd
     * @param string|\JsonSerializable $message
     * @param string                   $endpoint
     *
     * @return void
     */
    public function pushToFd($fd, $message, $endpoint = null)
    {
        if (is_array($fd)) {
            $fd = implode(',', $fd);
        }

        if (!is_string($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $endpoint = $endpoint ?: $this->_endpoint;

        if (!$endpoint) {
            throw new MissingFieldException('endpoint');
        }

        $this->_push($endpoint . ':fd', "$fd:$message");
    }

    /**
     * @param string|string[]          $user
     * @param string|\JsonSerializable $message
     * @param string                   $endpoint
     *
     * @return void
     */
    public function pushToUser($user, $message, $endpoint = null)
    {
        if (is_array($user)) {
            $user = implode(',', $user);
        }

        if (!is_string($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $endpoint = $endpoint ?: $this->_endpoint;

        if (!$endpoint) {
            throw new MissingFieldException('endpoint');
        }

        $this->_push($endpoint . ':user', "$user:$message");
    }

    /**
     * @param string|\JsonSerializable $message
     * @param string                   $endpoint
     *
     * @return void
     */
    public function pushToAll($message, $endpoint = null)
    {
        if (!is_string($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $endpoint = $endpoint ?: $this->_endpoint;

        if (!$endpoint) {
            throw new MissingFieldException('endpoint');
        }

        $this->_push($endpoint . ':all', "*:$message");
    }

    /**
     * @param string|array             $room
     * @param string|\JsonSerializable $message
     * @param array                    $excluded
     * @param string                   $endpoint
     *
     * @return void
     */
    public function pushToRoom($room, $message, $excluded = [], $endpoint = null)
    {
        $data = is_array($room) ? implode(',', $room) : $room;

        if ($excluded) {
            foreach ($excluded as $item) {
                $data .= '-' . $item;
            }
        }

        if (!is_string($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $endpoint = $endpoint ?: $this->_endpoint;

        if (!$endpoint) {
            throw new MissingFieldException('endpoint');
        }

        $this->_push($endpoint . ':room', "$data:$message");
    }
}