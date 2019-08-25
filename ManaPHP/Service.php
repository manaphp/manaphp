<?php
namespace ManaPHP;

use BadMethodCallException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Logger\LogCategorizable;
use ManaPHP\Service\Exception as ServiceException;
use ReflectionMethod;

/**
 * Class Service
 * @package ManaPHP
 */
class Service extends Component implements LogCategorizable
{
    /**
     * @var \ManaPHP\Rpc\ClientInterface
     */
    protected $_rpcClient;

    /**
     * @var string
     */
    protected $_interface;

    /**
     * @var array
     */
    protected $_parameters;

    /**
     * Service constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = null)
    {
        if (!$options) {
            return;
        }

        $endpoint = is_string($options) ? $options : $options['endpoint'];

        $scheme = parse_url($endpoint, PHP_URL_SCHEME);
        if ($scheme === 'ws' || $scheme === 'wss') {
            $class = 'ManaPHP\\Rpc\\Client\\Adapter\\JsonRpc';
        } elseif ($scheme === 'http' || $scheme === 'https') {
            $class = 'ManaPHP\\Rpc\\Client\\Adapter\\Rest';
        } else {
            throw new NotSupportedException(['`:type` type rpc is not support', 'type' => $scheme]);
        }

        if (is_string($options)) {
            $options = ['class' => $class, 'endpoint' => $options];
        } elseif (!isset($options[0]) && !isset($options['class'])) {
            $options['class'] = $class;
        }

        $this->_interface = $options['interface'];
        unset($options['interface']);

        if (isset($options['class'])) {
            $class = $options['class'];
            unset($options['class']);
        } else {
            $class = $options[0];
            unset($options[0]);
        }

        $this->_rpcClient = Di::getDefault()->get($class, $options);
    }

    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Service');
    }

    /**
     * @param string $method
     * @param array  $params
     *
     * @return mixed
     * @throws \ManaPHP\Service\Exception
     */
    public function invoke($method, $params)
    {
        $response = $this->_rpcClient->invoke($method, $params);

        if (!isset($response['code'], $response['message']) || (!isset($response['data']) && array_key_exists('data', $response))) {
            throw new ServiceException('bad response');
        } elseif ($response['code'] !== 0) {
            throw new ServiceException($response['message'], $response['code']);
        } else {
            return $response['data'];
        }
    }

    public function __call($method, $arguments)
    {
        if (!$parameters = $this->_parameters[$method] ?? null) {
            if (!$this->_interface) {
                throw new BadMethodCallException(get_called_class() . '->' . $method);
            }

            $rm = new ReflectionMethod($this->_interface, $method);
            $parameters = [];
            foreach ($rm->getParameters() as $parameter) {
                $name = $parameter->getName();
                $parameters[] = $parameter->isDefaultValueAvailable() ? [$name, $parameter->getDefaultValue()] : $name;
            }
            $this->_parameters[$method] = $parameters;
        }

        $params = [];
        foreach ($parameters as $i => $parameter) {
            $params[is_string($parameter) ? $parameter : $parameter[0]] = array_key_exists($i, $arguments) ? $arguments[$i] : $parameter[1];
        }

        return $this->invoke($method, $params);
    }
}