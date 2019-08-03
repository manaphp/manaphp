<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;
use ReflectionMethod;

/**
 * Class Service
 * @package ManaPHP
 * @property-read \ManaPHP\Rpc\ClientInterface $_rpcClient
 */
class Service extends Component implements LogCategorizable
{
    /**
     * @var array
     */
    protected $_parameters;

    /**
     * Service constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            foreach ($options as $name => $value) {
                $property = '_' . $name;

                if (property_exists($this, $property)) {
                    $this->$property = $value;
                }
            }
        }
    }

    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Service');
    }

    public function __call($method, $arguments)
    {
        if (!$parameters = $this->_parameters[$method] ?? null) {
            $rm = new ReflectionMethod(static::class . 'Interface', $method);
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

        return $this->_rpcClient->invoke($method, $params);
    }

    public function __get($name)
    {
        if ($name === '_rpcClient') {
            $service = static::class;
            return $this->_rpcClient = $this->_di->getShared('rpc' . substr($service, strrpos($service, '\\') + 1));
        } else {
            return parent::__get($name);
        }
    }
}