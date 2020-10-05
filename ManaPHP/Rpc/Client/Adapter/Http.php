<?php

namespace ManaPHP\Rpc\Client\Adapter;

use ManaPHP\Rpc\Client;
use ManaPHP\Rpc\Client\Exception as ClientException;
use ManaPHP\Rpc\Client\ProtocolException;

class Http extends Client
{
    /**
     * @var float
     */
    protected $_timeout = 3.0;

    /**
     * @var \ManaPHP\Http\ClientInterface
     */
    protected $_client;

    /**
     * JsonRpc constructor.
     *
     * @param array $options
     */
    public function __construct($options)
    {
        if (isset($options['timeout'])) {
            $this->_timeout = $options['timeout'];
        }

        if (!isset($options['keepalive'])) {
            $options['keepalive'] = true;
        }

        $endpoint = $options['endpoint'];
        unset($options['endpoint']);
        $this->_endpoint = str_contains($endpoint, '?') ? str_replace('/?', '?', $endpoint) : rtrim($endpoint, '/');

        if (isset($options[0])) {
            $client = $options[0];
            unset($options[0]);
        } elseif (isset($options['class'])) {
            $client = $options['class'];
            unset($options['class']);
        } else {
            $client = 'ManaPHP\Http\Client';
        }

        if (!isset($options['engine'])) {
            $options['engine'] = 'ManaPHP\Http\Client\Engine\Stream';
        }

        $this->_client = $this->getInstance($client, $options);
    }

    /**
     * @param string $endpoint
     *
     * @return static
     */
    public function setEndpoint($endpoint)
    {
        $this->_endpoint = $endpoint;

        return $this;
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

        $response = $this->_client->rest($method, $url, $params, [], $options)->body;

        if (!isset($response['code'], $response['message'])) {
            throw new ProtocolException('missing `code` or `message` field');
        }

        if ($response['code'] !== 0) {
            throw new ClientException($response['message'], $response['code']);
        }

        return $response['data'] ?? null;
    }
}