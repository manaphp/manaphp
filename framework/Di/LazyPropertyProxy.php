<?php

declare(strict_types=1);

namespace ManaPHP\Di;

use Psr\Container\ContainerInterface;
use ReflectionProperty;
use function call_user_func_array;
use function str_starts_with;

class LazyPropertyProxy implements Lazy
{
    protected ContainerInterface $container;
    protected ReflectionProperty $property;
    protected object $object;
    protected string $type;
    protected ?string $value = null;

    public function __construct(
        ContainerInterface $container,
        ReflectionProperty $property,
        object             $object,
        string             $type,
        ?string            $value
    )
    {
        $this->container = $container;
        $this->property = $property;
        $this->object = $object;
        $this->type = $type;
        $this->value = $value;
    }

    public function __call($name, $args)
    {
        $container = $this->container;

        $type = $this->type;
        $value = $this->value;
        if ($value !== null) {
            $value = $container->get(str_starts_with($value, '#') ? "$type$value" : $value);
        } else {
            $alias = "$type#" . $this->property->getName();
            $value = $container->has($alias) ? $container->get($alias) : $container->get($type);
        }

        $this->property->setValue($this->object, $value);

        return call_user_func_array([$value, $name], $args);
    }
}
