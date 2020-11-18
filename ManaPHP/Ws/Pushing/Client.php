<?php

namespace ManaPHP\Ws\Pushing;

use ManaPHP\Component;
use ManaPHP\Exception\MissingFieldException;

class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $_prefix = 'ws_pushing:';

    /**
     * @var string
     */
    protected $_endpoint;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['pubSub'])) {
            $this->_injections['pubSub'] = $options['pubSub'];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }

        if (isset($options['endpoint'])) {
            $this->_endpoint = $options['endpoint'];
        }
    }

    /**
     * @param string $channel
     * @param string $data
     *
     * @return void
     */
    protected function _push($channel, $data)
    {
        $this->pubSub->publish($this->_prefix . $channel, $data);
    }

    /**
     * @param int|int[]    $receivers
     * @param string|array $message
     * @param string       $endpoint
     *
     * @return void
     */
    public function pushToId($receivers, $message, $endpoint = null)
    {
        if (!is_string($message)) {
            $message = json_stringify($message);
        }

        if (!$endpoint = $endpoint ?: $this->_endpoint) {
            throw new MissingFieldException('endpoint');
        }

        $this->_push($endpoint . ':id', (is_array($receivers) ? implode(',', $receivers) : $receivers) . ":$message");
    }

    /**
     * @param string|string[] $receivers
     * @param string|array    $message
     * @param string          $endpoint
     *
     * @return void
     */
    public function pushToName($receivers, $message, $endpoint = null)
    {
        if (!is_string($message)) {
            $message = json_stringify($message);
        }

        if (!$endpoint = $endpoint ?: $this->_endpoint) {
            throw new MissingFieldException('endpoint');
        }

        $this->_push($endpoint . ':name', (is_array($receivers) ? implode(',', $receivers) : $receivers) . ":$message");
    }

    /**
     * @param string|string[] $receivers
     * @param string|array    $message
     * @param string          $endpoint
     *
     * @return void
     */
    public function pushToRole($receivers, $message, $endpoint = null)
    {
        if (!is_string($message)) {
            $message = json_stringify($message);
        }

        if (!$endpoint = $endpoint ?: $this->_endpoint) {
            throw new MissingFieldException('endpoint');
        }

        $this->_push($endpoint . ':role', (is_array($receivers) ? implode(',', $receivers) : $receivers) . ":$message");
    }

    /**
     * @param string|array $message
     * @param string       $endpoint
     *
     * @return void
     */
    public function pushToAll($message, $endpoint = null)
    {
        if (!is_string($message)) {
            $message = json_stringify($message);
        }

        if (!$endpoint = $endpoint ?: $this->_endpoint) {
            throw new MissingFieldException('endpoint');
        }

        $this->_push($endpoint . ':all', "*:$message");
    }

    /**
     * @param string|array $message
     * @param string       $endpoint
     *
     * @return void
     */
    public function broadcast($message, $endpoint = null)
    {
        if (!is_string($message)) {
            $message = json_stringify($message);
        }

        if (!$endpoint = $endpoint ?: $this->_endpoint) {
            throw new MissingFieldException('endpoint');
        }

        $this->_push($endpoint . ':broadcast', "*:$message");
    }
}
