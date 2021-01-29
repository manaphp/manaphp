<?php

namespace ManaPHP\Rpc\Client\Adapter;

use ManaPHP\Event\EventArgs;
use ManaPHP\Rpc\Client;
use ManaPHP\Rpc\Client\Exception as ClientException;
use ManaPHP\Rpc\Client\ProtocolException;

class Ws extends Client
{
    /**
     * @var float
     */
    protected $_timeout = 3.0;

    /**
     * @var bool
     */
    protected $_authentication;

    /**
     * @var int
     */
    protected $_id = 0;

    /**
     * @var client
     */
    protected $_client;

    /**
     * @param array $options
     */
    public function __construct($options)
    {
        $options['protocol'] = 'jsonrpc';

        $this->_endpoint = $options['endpoint'];

        if (isset($options['timeout'])) {
            $this->_timeout = $options['timeout'];
        }

        if (isset($options['authentication'])) {
            $this->_authentication = (bool)$options['authentication'];
            unset($options['authentication']);
        } else {
            $this->_authentication = preg_match('#[?&]token=#', $options['endpoint']) === 1;
        }

        $this->_client = $this->getNew('ManaPHP\Ws\Client', $options);

        if ($this->_authentication) {
            $this->_client->on(
                'open', function (EventArgs $eventArgs) {
                $this->authenticate($eventArgs->data);
            }
            );
        }
    }

    /**
     * @param string $endpoint
     *
     * @return static
     */
    public function setEndpoint($endpoint)
    {
        $this->_client->setEndpoint($endpoint);

        return $this;
    }

    /**
     * @param string $response
     *
     * @return mixed
     *
     * @throws \ManaPHP\Rpc\Client\Exception
     * @throws \ManaPHP\Rpc\Client\ProtocolException
     */
    protected function _parseResponse($response)
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

    /**
     * @param \ManaPHP\Ws\Client\EngineInterface $engine
     *
     * @return void
     */
    public function authenticate($engine)
    {
        $message = $engine->recv();

        try {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $success = false;
            $this->_parseResponse($message->payload);
            $success = true;
        } finally {
            if (!$success) {
                $engine->close();
            }
        }
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
        $request = json_stringify(['jsonrpc' => '2.0', 'method' => $method, 'params' => $params, 'id' => ++$this->_id]);

        $timout = $options['timeout'] ?? $this->_timeout;

        $message = $this->_client->request($request, $timout);

        $response = $this->_parseResponse($message->payload);

        if (!isset($response['code'], $response['message'])) {
            throw new ProtocolException('missing `code` or `message` field');
        }

        if ($response['code'] !== 0) {
            throw new ClientException($response['message'], $response['code']);
        }

        return $response['data'] ?? null;
    }
}