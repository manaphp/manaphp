<?php /** @noinspection MagicMethodsValidityInspection */

namespace ManaPHP\Rpc\Client;

use ManaPHP\Di\Injectable;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Str;
use ReflectionMethod;

class Service implements Injectable
{
    /**
     * @var mixed
     */
    protected $self;

    /**
     * @var \ManaPHP\Rpc\ClientInterface
     */
    protected $rpcClient;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var \ManaPHP\Di\ContainerInterface
     */
    protected $container;

    /**
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['endpoint' => $options];
        } elseif (!isset($options['endpoint'])) {
            throw new MisuseException('missing endpoint config');
        }

        $scheme = parse_url($options['endpoint'], PHP_URL_SCHEME);
        if ($scheme === 'ws' || $scheme === 'wss') {
            $class = 'ManaPHP\\Rpc\\Client\\Adapter\\Ws';
        } elseif ($scheme === 'http' || $scheme === 'https') {
            $class = 'ManaPHP\\Rpc\\Client\\Adapter\\Http';
        } else {
            throw new NotSupportedException(['`:type` type rpc is not support', 'type' => $scheme]);
        }

        $this->rpcClient = $this->container->getNew($class, $options);
    }

    public function setContainer($container, $self = null)
    {
        $this->container = $container;
        $this->self = $self ?? $this;
    }

    /**
     * @param string $method
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    protected function __rpcCall($method, $params = [], $options = [])
    {
        if ($pos = strpos($method, '::')) {
            $method = substr($method, $pos + 2);
        }

        if (count($params) !== 0 && key($params) === 0) {
            if (!$parameters = $this->parameters[$method] ?? []) {
                $rm = new ReflectionMethod($this, $method);
                foreach ($rm->getParameters() as $parameter) {
                    $parameters[] = $parameter->getName();
                }
                $this->parameters[$method] = $parameters;
            }

            if (count($parameters) !== count($params)) {
                throw new MisuseException('invoke parameter count is not match');
            }

            $params = array_combine($parameters, $params);
        }

        return $this->rpcClient->invoke($method, $params, $options);
    }
}