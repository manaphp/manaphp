<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Http\Client\Adapter;

use ManaPHP\Http\ClientInterface;
use ManaPHP\Rpc\Http\AbstractClient;
use ManaPHP\Rpc\Http\Client\Exception as ClientException;
use ManaPHP\Rpc\Http\Client\ProtocolException;

class Http extends AbstractClient
{
    protected float $timeout = 3.0;
    protected ClientInterface $client;

    public function __construct(array $options)
    {
        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }

        if (!isset($options['keepalive'])) {
            $options['keepalive'] = true;
        }

        $this->setEndpoint($options['endpoint']);
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

        $this->client = $this->container->make($client, $options);
    }

    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = str_contains($endpoint, '?') ? str_replace('/?', '?', $endpoint) : rtrim($endpoint, '/');

        return $this;
    }

    public function invoke(string $method, array $params = [], array $options = []): mixed
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