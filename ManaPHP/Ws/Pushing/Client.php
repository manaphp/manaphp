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
     * @param string       $type
     * @param string|array $receivers
     * @param string|array $message
     * @param string       $endpoint
     *
     * @return void
     */
    protected function push($type, $receivers, $message, $endpoint)
    {
        if (is_array($receivers)) {
            $receivers = implode(',', $receivers);
        }

        if (!is_string($message)) {
            $message = json_stringify($message);
        }

        if (($endpoint = $endpoint ?? $this->_endpoint) === null) {
            throw new MissingFieldException($endpoint);
        }

        $this->fireEvent('wspClient:push', compact('type', 'receivers', 'message', 'endpoint'));

        $this->pubSub->publish($this->_prefix . "$endpoint:$type:$receivers", $message);
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
        $this->push('id', $receivers, $message, $endpoint);
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
        $this->push('name', $receivers, $message, $endpoint);
    }

    /**
     * @param string|string[] $receivers
     * @param string|array    $message
     * @param string          $endpoint
     *
     * @return void
     */
    public function pushToRoom($receivers, $message, $endpoint = null)
    {
        $this->push('room', $receivers, $message, $endpoint);
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
        $this->push('role', $receivers, $message, $endpoint);
    }

    /**
     * @param string|array $message
     * @param string       $endpoint
     *
     * @return void
     */
    public function pushToAll($message, $endpoint = null)
    {
        $this->push('all', '*', $message, $endpoint);
    }

    /**
     * @param string|array $message
     * @param string       $endpoint
     *
     * @return void
     */
    public function broadcast($message, $endpoint = null)
    {
        $this->push('broadcast', '*', $message, $endpoint);
    }
}
