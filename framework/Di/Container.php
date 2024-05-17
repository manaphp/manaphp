<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config as ConfigAttribute;
use ManaPHP\Di\Event\SingletonCreated;
use ManaPHP\Exception\MisuseException;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use function array_key_exists;
use function interface_exists;
use function is_array;
use function is_object;
use function is_string;
use function str_contains;
use function substr;

class Container implements ContainerInterface
{
    protected array $definitions = [];
    protected array $instances = [];

    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;

        $this->definitions['Psr\Container\ContainerInterface'] = $this;
        $this->definitions['ManaPHP\Di\ContainerInterface'] = $this;
        $this->definitions['ManaPHP\Di\MakerInterface'] = $this;
        $this->definitions['ManaPHP\Di\InvokerInterface'] = $this;
    }

    protected function dispatchEvent(object $event): void
    {
        if (($dispatcher = $this->instances[EventDispatcherInterface::class] ?? null) !== null) {
            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher->dispatch($event);
        }
    }

    public function set(string $id, mixed $definition): static
    {
        if (isset($this->instances[$id])) {
            throw new MisuseException(['it\'s too late to set(): `{1}` instance has been created', $id]);
        }

        if ($definition instanceof Pool) {
            foreach ($definition->pool as $name => $def) {
                if (is_string($def) && $def[0] === '#') {
                    $def = "$id$def";
                }

                $this->set("$id#$name", $def);

                if ($name === 'default') {
                    $this->set($id, "#$name");
                }
            }
        } else {
            $this->definitions[$id] = $definition;
        }

        return $this;
    }

    public function remove(string $id): static
    {
        unset($this->definitions[$id], $this->instances[$id]);

        return $this;
    }

    protected function injectObject(ReflectionProperty $property, object $object, array $parameters): void
    {
        $name = $property->getName();

        if (($value = $parameters[$name] ?? null) === null || is_string($value)) {
            if (($rType = $property->getType()) === null) {
                throw new Exception(sprintf('The type of `%s::%s` is missing.', $object::class, $name));
            }

            if ($rType instanceof ReflectionUnionType) {
                $value = new Proxy($this, $property, $object, $parameters[$name] ?? null);
            } else {
                $type = $rType->getName();

                if ($value !== null) {
                    if (is_string($value)) {
                        $value = $this->get($value[0] === '#' ? "$type$value" : $value);
                    }
                } else {
                    $value = $this->get($type);
                }
            }
        }

        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }
        $property->setValue($object, $value);
    }

    protected function injectNoValue(ReflectionProperty $property, $object): void
    {
        $rType = $property->getType();

        if ($rType !== null && $rType->allowsNull()) {
            if (!$property->isPublic()) {
                $property->setAccessible(true);
            }
            $property->setValue($object, null);
        } else {
            throw new Exception(
                sprintf('The property value of `%s::$%s` is not provided.', $property->class, $property->getName())
            );
        }
    }

    protected function injectValue(ReflectionProperty $property, object $object, array $parameters): void
    {
        $name = $property->getName();

        if (array_key_exists($name, $parameters)) {
            if (!$property->isPublic()) {
                $property->setAccessible(true);
            }
            $property->setValue($object, $parameters[$name]);
        } elseif (!$property->hasDefaultValue() && $property->hasType()) {
            $this->injectNoValue($property, $object);
        }
    }

    protected function injectConfig(ReflectionProperty $property, object $object, array $parameters): void
    {
        $name = $property->getName();

        if (array_key_exists($name, $parameters)) {
            if (!$property->isPublic()) {
                $property->setAccessible(true);
            }
            $property->setValue($object, $parameters[$name]);
        } elseif (($config = $this->get(ConfigInterface::class))->has($name)) {
            if (!$property->isPublic()) {
                $property->setAccessible(true);
            }
            $property->setValue($object, $config->get($name));
        } elseif (!$property->hasDefaultValue() && $property->hasType()) {
            $this->injectNoValue($property, $object);
        }
    }

    protected function injectPropertiesInternal(object $object, ReflectionClass $rClass, array $parameters): void
    {
        foreach ($rClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $attributes = [];
            foreach ($property->getAttributes() as $attribute) {
                $attributes[$attribute->getName()] = $attribute;
            }

            if ($attributes === []) {
                continue;
            }

            if (isset($attributes[Autowired::class])) {
                if ($property->hasType()) {
                    $rType = $property->getType();
                    $type = $rType instanceof ReflectionNamedType ? $rType : $rType->getTypes()[0];

                    if ($type->isBuiltin()) {
                        $this->injectValue($property, $object, $parameters);
                    } else {
                        $this->injectObject($property, $object, $parameters);
                    }
                } else {
                    throw new Exception(
                        sprintf('The type of `%s::%s` is missing.', $object::class, $property->getName())
                    );
                }
            } elseif (isset($attributes[ConfigAttribute::class])) {
                $this->injectConfig($property, $object, $parameters);
            }
        }
    }

    public function injectProperties(object $object, array $parameters = []): object
    {
        $this->injectPropertiesInternal($object, new ReflectionClass($object), $parameters);

        return $object;
    }

    protected function makeInternal(string $name, array $parameters = [], string $id = null): object
    {
        $rClass = new ReflectionClass($name);
        if (method_exists($name, '__construct')) {
            $instance = $rClass->newInstanceWithoutConstructor();

            if ($id !== null) {
                $this->instances[$id] = $instance;
            }

            $this->injectPropertiesInternal($instance, $rClass, $parameters);

            $this->call([$instance, '__construct'], $parameters);
        } else {
            $instance = new $name();

            if ($id !== null) {
                $this->instances[$id] = $instance;
            }

            $this->injectPropertiesInternal($instance, $rClass, $parameters);
        }

        return $instance;
    }

    public function make(string $name, array $parameters = [], string $id = null): mixed
    {
        while (is_string($definition = $this->definitions[$name] ?? null) && !str_contains($definition, '#')) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $name = $definition;
        }

        if (preg_match('#^[\w\\\\]+$#', $name) !== 1) {
            throw new NotFoundException(['{1} not found', $name]);
        }

        $exists = false;
        /** @noinspection NotOptimalIfConditionsInspection */
        if (str_ends_with($name, 'Interface') && interface_exists($name)) {
            $prefix = substr($name, 0, -9);
            if (class_exists($prefix)) {
                $exists = true;
                $name = $prefix;
            }
        } elseif (class_exists($name)) {
            $exists = true;
        }

        if (!$exists) {
            throw new NotFoundException(['`{1}` is not exists', $name]);
        }

        if (method_exists($name, '__invoke')) {
            if (($object = $this->instances[$name] ?? null) === null) {
                $object = $this->makeInternal($name, [], $name);
            }
            $instance = $this->call([$object, '__invoke'], compact('parameters', 'id'));
        } else {
            $instance = $this->makeInternal($name, $parameters, $id);
        }

        if ($id !== null) {
            $this->dispatchEvent(new SingletonCreated($id, $instance, $this->definitions));
        }

        return $instance;
    }

    public function get(string $id): mixed
    {
        if (($instance = $this->instances[$id] ?? null) !== null) {
            return $instance;
        } elseif (($definition = $this->definitions[$id] ?? null) === null) {
            if (str_contains($id, '#')) {
                throw new Exception(sprintf('The definition of `%s` is not found.', $id));
            }

            $instance = $this->make($id, [], $id);
            if (class_exists($id, false) && interface_exists($id . 'Interface', false)) {
                unset($this->instances[$id]);
                throw new MisuseException(sprintf('please autowire using %sInterface to replace %s.', $id, $id));
            }

            return $this->instances[$id] = $instance;
        } elseif (is_object($definition)) {
            return $this->instances[$id] = $definition;
        } elseif (is_array($definition)) {
            if (($class = $definition['class'] ?? null) !== null) {
                unset($definition['class']);
            } else {
                $class = ($position = strpos($id, '#')) === false ? $id : substr($id, 0, $position);
            }

            return $this->instances[$id] = $this->make($class, $definition, $id);
        } elseif (!is_string($definition)) {
            throw new Exception(sprintf('The definition of `%s` is not supported.', $id));
        } elseif (str_contains($definition, '#')) {
            return $this->instances[$id] = $this->get($definition[0] === '#' ? "$id$definition" : $definition);
        } elseif (interface_exists($definition)) {
            return $this->instances[$id] = $this->get($definition);
        } else {
            return $this->instances[$id] = $this->make($definition, [], $id);
        }
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    public function getDefinition(string $id): mixed
    {
        return $this->definitions[$id] ?? null;
    }

    public function getInstances(): array
    {
        return $this->instances;
    }

    public function has(string $id): bool
    {
        if (isset($this->instances[$id])) {
            return true;
        } elseif (isset($this->definitions[$id])) {
            return true;
        } elseif (str_contains($id, '.')) {
            $glob = substr($id, 0, strrpos($id, '.')) . '.*';
            return isset($this->definitions[$glob]);
        } else {
            return interface_exists($id) || class_exists($id);
        }
    }

    public function call(callable $callable, array $parameters = []): mixed
    {
        if (is_array($callable)) {
            $rFunction = new ReflectionMethod($callable[0], $callable[1]);
        } else {
            $rFunction = new ReflectionFunction($callable);
        }

        $args = [];
        foreach ($rFunction->getParameters() as $position => $rParameter) {
            $name = $rParameter->getName();

            $rType = $rParameter->getType();
            $type = ($rType instanceof ReflectionNamedType && !$rType->isBuiltin()) ? $rType->getName() : null;

            if (array_key_exists($position, $parameters)) {
                $value = $parameters[$position];
            } elseif (array_key_exists($name, $parameters)) {
                $value = $parameters[$name];
            } elseif ($rParameter->isDefaultValueAvailable()) {
                $value = $rParameter->getDefaultValue();
            } elseif ($type !== null) {
                $value = $parameters[$type] ?? $type;
            } else {
                $signature = is_array($callable)
                    ? $callable[0]::class . '::' . $callable[1]
                    : $rFunction->getName();
                throw new Exception(sprintf('Cannot autowire argument `$%s` of method %s().', $name, $signature));
            }

            if ($type !== null && is_string($value)) {
                $value = $this->get($value[0] === '#' ? "$type$value" : $value);
            }

            $args[] = $value;
        }

        return $callable(...$args);
    }
}
