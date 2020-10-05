<?php

namespace ManaPHP\Rpc\Client\Adapter;

use ManaPHP\Rpc\Client;
use ManaPHP\Rpc\Client\Exception as ClientException;
use ManaPHP\Rpc\Client\ProtocolException;

class Http extends Client
{
    /**
     * @var string
     */
    protected $_endpoint;

    /**
     * @var float
     */
    protected $_timeout = 3.0;

    /**
     * @var int
     */
    protected $_pool_size = 4;

    /**
     * JsonRpc constructor.
     *
     * @param array $options
     */
    public function __construct($options)
    {
        if (isset($options['pool_size'])) {
            $this->_pool_size = $options['pool_size'];
            unset($options['pool_size']);
        }

        if (isset($options['timeout'])) {
            $this->_timeout = $options['timeout'];
        }

        if (!isset($options['keepalive'])) {
            $options['keepalive'] = true;
        }

        $endpoint = $options['endpoint'];
        unset($options['endpoint']);
        $this->_endpoint = str_contains($endpoint, '?') ? str_replace('/?', '?', $endpoint) : rtrim($endpoint, '/');

        if (!isset($options[0]) && !isset($options['class'])) {
            $options['class'] = 'ManaPHP\Http\Client';
        }

        if (!isset($options['engine'])) {
            $options['engine'] = 'ManaPHP\Http\Client\Engine\Stream';
        }

        $this->poolManager->add($this, $options, $this->_pool_size);
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    /**
     * @param string          $method
     * @param array           $params
     * @param array|int|float $options
     *
     * @return mixed
     */
    public function invoke($method, $params = [], $options = null)
    {
        $endpoint = $this->_endpoint;
        $url = str_contains($endpoint, '?') ? str_replace('?', "/$method?", $endpoint) : "$endpoint/$method";

        if (isset($options['method'])) {
            $method = $options['method'];
            unset($options['method']);
        } else {
            $method = 'POST';
        }

        /** @var \ManaPHP\Http\ClientInterface $client */
        $client = $this->poolManager->pop($this, $this->_timeout);
        try {
            $response = $client->rest($method, $url, $params, [], $options)->body;
        } finally {
            $this->poolManager->push($this, $client);
        }

        if (!isset($response['code'], $response['message'])) {
            throw new ProtocolException('missing `code` or `message` field');
        }

        if ($response['code'] !== 0) {
            throw new ClientException($response['message'], $response['code']);
        }

        return $response['data'] ?? null;
    }
}