<?php

namespace ManaPHP\Di;

use ManaPHP\Exception\MissingFieldException;
use ReflectionFunction;
use ReflectionMethod;

class Invoker implements InvokerInterface
{
    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     * @param callable                       $callable
     * @param array                          $parameters
     *
     * @return mixed
     */
    public function call($container, $callable, $parameters = [])
    {
        if (is_array($callable)) {
            $reflectionFunction = new ReflectionMethod($callable[0], $callable[1]);
        } else {
            $reflectionFunction = new ReflectionFunction($callable);
        }

        $missing = [];
        $args = [];
        foreach ($reflectionFunction->getParameters() as $position => $reflectionParameter) {
            $name = $reflectionParameter->getName();
            $reflectionType = $reflectionParameter->getType();

            if (array_key_exists($position, $parameters)) {
                $value = $parameters[$position];
            } elseif (array_key_exists($name, $parameters)) {
                $value = $parameters[$name];
            } elseif ($reflectionParameter->isDefaultValueAvailable()) {
                $args[] = $reflectionParameter->getDefaultValue();
                continue;
            } elseif ($reflectionType !== null && !$reflectionType->isBuiltin()) {
                $type = $reflectionType->getName();
                if ($container->has($type)) {
                    $value = $container->get($type);
                } else {
                    $missing[] = $name;
                    continue;
                }
            } else {
                $missing[] = $name;
                continue;
            }

            if ($reflectionType === null) {
                null;
            } elseif ($reflectionType->isBuiltin()) {
                $type = $reflectionType->getName();
                if ($type === 'string') {
                    $value = (string)$value;
                } elseif ($type === 'int') {
                    $value = (int)$value;
                } elseif ($type === 'float') {
                    $value = (float)$value;
                } elseif ($type === 'bool') {
                    if (!is_bool($value)) {
                        if ($value === '' || str_contains(',0,false,off,no,', ",$value,")) {
                            $value = false;
                        } else {
                            $value = true;
                        }
                    }
                }
            }
            $args[] = $value;
        }

        if ($missing) {
            throw new MissingFieldException(implode(",", $missing));
        }

        return $callable(...$args);
    }
}