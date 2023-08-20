<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionProperty;

class Proxy
{
    protected ContainerInterface $container;
    protected ReflectionProperty $property;
    protected object $object;
    protected ?string $value = null;

    public function __construct(PsrContainerInterface $container, ReflectionProperty $property, object $object,
        ?string $value
    ) {
        $this->container = $container;
        $this->property = $property;
        $this->object = $object;
        $this->value = $value;
    }

    public function __call($name, $args)
    {
        $id = $this->proxyGetId();

        $target = $this->container->get($id);

        if (!$this->property->isPublic()) {
            $this->property->setAccessible(true);
        }
        $this->property->setValue($this->object, $target);

        return call_user_func_array([$target, $name], $args);
    }

    protected function proxyGetId(): string
    {
        $proxy = false;
        $id = null;
        foreach ($this->property->getType()->getTypes() as $rType) {
            $type = $rType->getName();
            if ($type === static::class) {
                $proxy = true;
            } else {
                $id = $type;
            }
        }

        $object = $this->object;
        if (!$proxy) {
            throw new Exception(sprintf('%s::%s is not proxied', $object::class, $this->property->getName()));
        }

        if ($id === null) {
            throw new Exception('no type');
        }

        $value = $this->value;
        if ($value !== null) {
            $id = $value[0] === '#' ? "$id$value" : $value;
        }

        return $id;
    }
}