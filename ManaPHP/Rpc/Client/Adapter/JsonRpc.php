<?php
namespace ManaPHP\Rpc\Client\Adapter;

use ManaPHP\Component;
use ManaPHP\Rpc\Client\Exception as ClientException;
use ManaPHP\Rpc\Client\ProtocolException;
use ManaPHP\Rpc\ClientInterface;

class JsonRpc extends Component implements ClientInterface
{
    /**
     * @var float
     */
    protected $_timeout = 3.0;

    /**
     * @var int
     */
    protected $_pool_size = 4;

    /**
     * @var bool
     */
    protected $_authentication;

    /**
     * @var int
     */
    protected $_id = 0;

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

        $options['protocol'] = 'jsonrpc';

        if (isset($options['timeout'])) {
            $this->_timeout = $options['timeout'];
        }

        if (!isset($options[0]) && !isset($options['class'])) {
            $options['class'] = 'ManaPHP\WebSocket\Client';
        }

        if (isset($options['authentication'])) {
            $this->_authentication = $options['authentication'];
            unset($options['authentication']);
        } else {
            $this->_authentication = preg_match('#[?&]token=#', $options['endpoint']) === 1;
        }

        if ($this->_authentication) {
            $options['on_connect'] = [$this, 'authenticate'];
        }

        $this->poolManager->add($this, $options, $this->_pool_size);
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    /**
     * @param $response
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
     * @param \ManaPHP\WebSocket\ClientInterface $client
     */
    public function authenticate($client)
    {
        $response = $client->recv();

        try {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $success = false;
            $this->_parseResponse($response);
            $success = true;
        } finally {
            if (!$success) {
                $client->close();
            }
        }
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
        $request = json_stringify(['jsonrpc' => '2.0', 'method' => $method, 'params' => $params, 'id' => ++$this->_id]);

        /** @var \ManaPHP\WebSocket\ClientInterface $client */
        $client = $this->poolManager->pop($this, $this->_timeout);

        try {
            $client->send($request);
            $response = $client->recv();
        } finally {
            $this->poolManager->push($this, $client);
        }

        return $this->_parseResponse($response);
    }
}