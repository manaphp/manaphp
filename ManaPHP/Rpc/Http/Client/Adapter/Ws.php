<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Http\Client\Adapter;

use ManaPHP\Rpc\Http\AbstractClient;
use ManaPHP\Rpc\Http\Client\Exception as ClientException;
use ManaPHP\Rpc\Http\Client\ProtocolException;
use ManaPHP\Ws\Client\EngineInterface;
use ManaPHP\Ws\ClientInterface;

class Ws extends AbstractClient
{
    protected float $timeout = 3.0;
    protected bool $authentication;
    protected int $id = 0;
    protected ClientInterface $client;

    public function __construct(array $options)
    {
        $options['protocol'] = 'jsonrpc';

        $this->endpoint = $options['endpoint'];

        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }

        if (isset($options['authentication'])) {
            $this->authentication = (bool)$options['authentication'];
            unset($options['authentication']);
        } else {
            $this->authentication = preg_match('#[?&]token=#', $options['endpoint']) === 1;
        }

        $this->client = $this->container->make('ManaPHP\Ws\Client', $options);

        if ($this->authentication) {
            $this->client->on('open', [$this, 'authenticate']);
        }
    }

    public function setEndpoint(string $endpoint): static
    {
        $this->client->setEndpoint($endpoint);

        return $this;
    }

    protected function parseResponse(string $response): mixed
    {
        $json = json_parse($response);

        if (!isset($json['jsonrpc']) || $json['jsonrpc'] !== '2.0') {
            throw new ProtocolException('');
        }

        if (isset($json['error'])) {
            $error = $json['error'];
            if (!isset($error['code'], $error['message'])) {
                throw new ProtocolException($error['message'], $error['code']);
            }
            throw new ClientException($error['message'], $error['code']);
        } elseif (!isset($json['result'])) {
            throw new ProtocolException('missing result field');
        } else {
            return $json['result'];
        }
    }

    public function authenticate(EngineInterface $engine): void
    {
        $message = $engine->recv();

        try {
            $success = false;
            $this->parseResponse($message->payload);
            $success = true;
        } finally {
            if (!$success) {
                $engine->close();
            }
        }
    }

    public function invoke(string $method, array $params = [], array $options = []): mixed
    {
        $request = json_stringify(['jsonrpc' => '2.0', 'method' => $method, 'params' => $params, 'id' => ++$this->id]);

        $timout = $options['timeout'] ?? $this->timeout;

        $message = $this->client->request($request, $timout);

        $response = $this->parseResponse($message->payload);

        if (!isset($response['code'], $response['message'])) {
            throw new ProtocolException('missing `code` or `message` field');
        }

        if ($response['code'] !== 0) {
            throw new ClientException($response['message'], $response['code']);
        }

        return $response['data'] ?? null;
    }
}