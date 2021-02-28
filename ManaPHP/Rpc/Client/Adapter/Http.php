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
    protected $timeout = 3.0;

    /**
     * @var \ManaPHP\Http\ClientInterface
     */
    protected $client;

    /**
     * @param array $options
     */
    public function __construct($options)
    {
        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }

        if (!isset($options['keepalive'])) {
            $options['keepalive'] = true;
        }

        $this->self->setEndpoint($options['endpoint']);
        unset($options['endpoint']);

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

        $this->client = $this->getNew($client, $options);
    }

    /**
     * @param string $endpoint
     *
     * @return static
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = str_contains($endpoint, '?') ? str_replace('/?', '?', $endpoint) : rtrim($endpoint, '/');

        return $this;
    }

    /**
     * @param string $method
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    public function invoke($method, $params = [], $options = [])
    {
        $endpoint = $this->endpoint;
        $url = str_contains($endpoint, '?') ? str_replace('?', "/$method?", $endpoint) : "$endpoint/$method";

        if (isset($options['method'])) {
            $method = $options['method'];
            unset($options['method']);
        } else {
            $method = 'POST';
        }

        $response = $this->client->rest($method, $url, $params, [], $options)->body;

        if (!isset($response['code'], $response['message'])) {
            throw new ProtocolException('missing `code` or `message` field');
        }

        if ($response['code'] !== 0) {
            throw new ClientException($response['message'], $response['code']);
        }

        return $response['data'] ?? null;
    }
}