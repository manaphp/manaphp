<?php

namespace ManaPHP\Di;

use ManaPHP\Exception\MissingFieldException;
use ReflectionFunction;

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
            $reflectionFunction = new \ReflectionMethod($callable[0], $callable[1]);
        } else {
            $reflectionFunction = new ReflectionFunction($callable);
        }
        $missing = [];
        $args = [];
        foreach ($reflectionFunction->getParameters() as $position => $reflectionParameter) {
            if (array_key_exists($position, $parameters)) {
                $args[] = $parameters[$position];
                continue;
            }

            $name = $reflectionParameter->getName();
            $reflectionType = $reflectionParameter->getType();

            if ($reflectionType === null) {
                if (array_key_exists($name, $parameters)) {
                    $value = $parameters[$name];
                } elseif ($reflectionParameter->isDefaultValueAvailable()) {
                    $value = $reflectionParameter->getDefaultValue();
                } else {
                    $value = null;
                    $missing[] = $name;
                }
            } elseif ($reflectionType->isBuiltin()) {
                if (array_key_exists($name, $parameters)) {
                    $value = $parameters[$name];
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
                } elseif ($reflectionParameter->isDefaultValueAvailable()) {
                    $value = $reflectionParameter->getDefaultValue();
                } else {
                    $value = null;
                    $missing[] = $name;
                }
            } elseif ($container->has($name)) {
                $value = $container->get($name);
            } elseif ($reflectionParameter->isDefaultValueAvailable()) {
                $value = $reflectionParameter->getDefaultValue();
            } else {
                $value = null;
                $missing[] = $name;
            }
            $args[] = $value;
        }

        if ($missing) {
            throw new MissingFieldException(implode(",", $missing));
        } else {
            return $callable(...$args);
        }
    }
}