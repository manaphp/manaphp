<?php

namespace ManaPHP\Rpc\Client;

use ManaPHP\Di;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ReflectionMethod;

class Service extends \ManaPHP\Service
{
    /**
     * @var string
     */
    protected $_endpoint;

    /**
     * @var \ManaPHP\Rpc\ClientInterface
     */
    protected $_rpcClient;

    /**
     * @var array
     */
    protected $_parameters;

    /**
     * Service constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['endpoint' => $options];
        }

        parent::__construct($options);

        if (!$endpoint = $this->_endpoint) {
            throw new MisuseException('missing endpoint config');
        }

        $scheme = parse_url($endpoint, PHP_URL_SCHEME);
        if ($scheme === 'ws' || $scheme === 'wss') {
            $class = 'ManaPHP\\Rpc\\Client\\Adapter\\JsonRpc';
        } elseif ($scheme === 'http' || $scheme === 'https') {
            $class = 'ManaPHP\\Rpc\\Client\\Adapter\\Rest';
        } else {
            throw new NotSupportedException(['`:type` type rpc is not support', 'type' => $scheme]);
        }

        $this->_rpcClient = Di::getDefault()->get($class, $options);
    }

    /**
     * @param string $method
     * @param array  $params
     *
     * @return mixed
     */
    public function invoke($method, $params = [])
    {
        if ($pos = strpos($method, '::')) {
            $method = substr($method, $pos + 2);
        }

        if (count($params) !== 0 && key($params) === 0) {
            if (!$parameters = $this->_parameters[$method] ?? []) {
                $rm = new ReflectionMethod($this, $method);
                foreach ($rm->getParameters() as $parameter) {
                    $parameters[] = $parameter->getName();
                }
                $this->_parameters[$method] = $parameters;
            }

            if (count($parameters) !== count($params)) {
                throw new MisuseException('invoke parameter count is not match');
            }

            $params = array_combine($parameters, $params);
        }

        return $this->_rpcClient->invoke($method, $params);
    }
}