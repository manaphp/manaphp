<?php /** @noinspection MagicMethodsValidityInspection */
declare(strict_types=1);

namespace ManaPHP\Rpc;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\Injectable;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ReflectionMethod;

class Service implements Injectable
{
    protected ClientInterface $rpcClient;
    protected array $parameters;
    protected ContainerInterface $container;

    public function __construct(string|array $options = [])
    {
        if (is_string($options)) {
            $options = ['endpoint' => $options];
        } elseif (!isset($options['endpoint'])) {
            throw new MisuseException('missing endpoint config');
        }

        $scheme = parse_url($options['endpoint'], PHP_URL_SCHEME);
        if ($scheme === 'ws' || $scheme === 'wss') {
            $class = 'ManaPHP\Rpc\Http\Client\Adapter\Ws';
        } elseif ($scheme === 'http' || $scheme === 'https') {
            $class = 'ManaPHP\Rpc\Http\Client\Adapter\Http';
        } else {
            throw new NotSupportedException(['`:type` type rpc is not support', 'type' => $scheme]);
        }

        $this->rpcClient = $this->container->make($class, $options);
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    protected function __rpcCall(string $method, array $params = [], array $options = []): mixed
    {
        if ($pos = strpos($method, '::')) {
            $method = substr($method, $pos + 2);
        }

        if (count($params) !== 0 && key($params) === 0) {
            if (!$parameters = $this->parameters[$method] ?? []) {
                $rMethod = new ReflectionMethod($this, $method);
                foreach ($rMethod->getParameters() as $rParameter) {
                    $parameters[] = $rParameter->getName();
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