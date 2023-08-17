<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Exception\MisuseException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

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

    public function set(string $id, mixed $definition): static
    {
        if (isset($this->instances[$id])) {
            throw new MisuseException(['it\'s too late to set(): `%s` instance has been created', $id]);
        }

        $this->definitions[$id] = $definition;

        return $this;
    }

    public function remove(string $id): static
    {
        unset($this->definitions[$id], $this->instances[$id]);

        return $this;
    }

    protected function processInject(object $object, ReflectionClass $reflectionClass, array $parameters): void
    {
        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if ($property->getAttributes(Inject::class) !== []) {
                $type = $property->getType()->getName();
                $id = $parameters[$type] ?? $type;

                $value = $this->get($id[0] === '#' ? "$type$id" : $id);
                if (!$property->isPublic()) {
                    $property->setAccessible(true);
                }
                $property->setValue($object, $value);
            }
        }
    }

    protected function processValue(object $object, ReflectionClass $reflectionClass, array $parameters): void
    {
        foreach ($parameters as $name => $value) {
            if (is_string($name) && !str_contains($name, '\\')) {
                if (!$reflectionClass->hasProperty($name)) {
                    continue;
                }
                $property = $reflectionClass->getProperty($name);
                if ($property->getAttributes(Value::class) !== []) {
                    if (!$property->isPublic()) {
                        $property->setAccessible(true);
                    }
                    $property->setValue($object, $value);
                }
            }
        }
    }

    protected function makeInternal(string $name, array $parameters = [], string $id = null): object
    {
        $rClass = new ReflectionClass($name);
        if (method_exists($name, '__construct')) {
            $instance = $rClass->newInstanceWithoutConstructor();

            if ($id !== null) {
                $this->instances[$id] = $instance;
            }

            $this->processInject($instance, $rClass, $parameters);
            $this->processValue($instance, $rClass, $parameters);

            $this->call([$instance, '__construct'], $parameters);
        } else {
            $instance = new $name();

            if ($id !== null) {
                $this->instances[$id] = $instance;
            }

            $this->processInject($instance, $rClass, $parameters);
            $this->processValue($instance, $rClass, $parameters);
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
            throw new NotFoundException(["%s not found", $name]);
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
            throw new NotFoundException(['`%s` is not exists', $name]);
        }

        if (method_exists($name, '__invoke')) {
            if (($object = $this->instances[$name] ?? null) === null) {
                $object = $this->makeInternal($name, [], $name);
            }
            return $this->call([$object, '__invoke'], compact('parameters', 'id'));
        } else {
            return $this->makeInternal($name, $parameters, $id);
        }
    }

    protected function getInternal(string $id, mixed $definition): mixed
    {
        if (is_string($definition)) {
            return $this->make($definition, [], $id);
        } elseif (is_object($definition)) {
            return $definition;
        } elseif (is_array($definition)) {
            if (($class = $definition['class'] ?? null) !== null) {
                unset($definition['class']);
            } else {
                $class = $id;
            }

            return $this->make($class, $definition, $id);
        } else {
            throw new Exception(sprintf('The definition of `%s` is not supported.', $id));
        }
    }

    public function get(string $id): mixed
    {
        if (($instance = $this->instances[$id] ?? null) !== null) {
            return $instance;
        }

        if (($definition = $this->definitions[$id] ?? null) !== null) {
            for (; ;) {
                if (is_string($definition)) {
                    if ($definition[0] === '#') {
                        $definition = "$id$definition";
                    }

                    if (str_contains($definition, '#')) {
                        if (($definition = $this->definitions[$definition] ?? null) === null) {
                            throw new Exception(sprintf('The definition of `%s` is not found.', $id));
                        }
                    } else {
                        if (($v = $this->definitions[$definition] ?? null) !== null) {
                            $definition = $v;
                        } else {
                            break;
                        }
                    }
                } else {
                    break;
                }
            }
        } else {
            $definition = $id;
        }

        return $this->instances[$id] = $this->getInternal($id, $definition);
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
