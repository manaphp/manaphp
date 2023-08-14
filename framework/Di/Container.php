<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use Closure;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Primary;
use ManaPHP\Exception\MisuseException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use WeakMap;

class Container implements ContainerInterface, MakerInterface, InvokerInterface, InspectorInterface
{
    protected array $definitions = [];
    protected array $instances = [];
    protected WeakMap $dependencies;

    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;

        $this->definitions['Psr\Container\ContainerInterface'] = $this;
        $this->definitions['ManaPHP\Di\MakerInterface'] = $this;
        $this->definitions['ManaPHP\Di\InvokerInterface'] = $this;
        $this->definitions['ManaPHP\Di\InspectorInterface'] = $this;

        $this->dependencies = new WeakMap();
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

    protected function processInject(object $object, ReflectionClass $reflectionClass): void
    {
        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if ($property->getAttributes(Inject::class) !== []) {
                $type = $property->getType()->getName();
                $dependencies = $this->dependencies[$object] ?? null;
                $id = $dependencies[$property] ?? $dependencies[$type] ?? $type;

                $value = $this->get($id[0] === '#' ? "$type$id" : $id);
                if (!$property->isPublic()) {
                    $property->setAccessible(true);
                }
                $property->setValue($object, $value);
            }
        }
    }

    public function make(string $name, array $parameters = [], string $id = null): mixed
    {
        while (is_string($definition = $this->definitions[$name] ?? null)) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $name = $definition;
        }

        if (str_contains($name, '::')) {
            list($factory, $method) = explode('::', $name);
            return $this->call([$this->get($factory), $method], $parameters);
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
            } else {
                $rClass = new ReflectionClass($name);
                if (($attribute = $rClass->getAttributes(Primary::class)[0] ?? null) !== null) {
                    /** @var Primary $primary */
                    $primary = $attribute->newInstance();
                    return $this->make($primary->definition, $parameters);
                }
            }
        } elseif (class_exists($name)) {
            $exists = true;
        }

        if (!$exists) {
            throw new NotFoundException(['`%s` is not exists', $name]);
        }

        $rClass = new ReflectionClass($name);
        if (method_exists($name, '__construct')) {
            $instance = $rClass->newInstanceWithoutConstructor();

            if ($id !== null) {
                $this->instances[$id] = $instance;
            }

            if ($parameters !== []) {
                $dependencies = [];
                foreach ($parameters as $key => $value) {
                    if (is_string($key)) {
                        $dependencies[$key] = $value;
                    }
                }

                if ($dependencies !== []) {
                    $rMethod = $rClass->getMethod('__construct');
                    foreach ($rMethod->getParameters() as $rParameter) {
                        unset($dependencies[$rParameter->getName()]);
                    }

                    if ($dependencies !== []) {
                        $this->dependencies[$instance] = $dependencies;
                    }
                }
            }

            $this->processInject($instance, $rClass);

            $this->call([$instance, '__construct'], $parameters);
        } else {
            $instance = new $name();

            if ($id !== null) {
                $this->instances[$id] = $instance;
            }

            if ($parameters !== []) {
                $this->dependencies[$instance] = $parameters;
            }

            $this->processInject($instance, $rClass);
        }

        return $instance;
    }

    public function getInternal(string $id, mixed $definition): mixed
    {
        if (is_string($definition)) {
            if (str_contains($definition, '::')) {
                return $this->make($definition, ['id' => $id], $id);
            } else {
                return $this->make($id, [], $definition);
            }
        } elseif ($definition instanceof Closure) {
            return $this->call($definition);
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
            throw new NotSupportedException('not supported definition');
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
                            throw new DefinitionException(sprintf('The definition of `%s` is not found.', $id));
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
                throw new AutowiringFailedException(
                    sprintf(
                        'Cannot autowire argument "$%s" of method "%s()", you should configure its value explicitly.',
                        $name, $signature
                    )
                );
            }

            if ($type !== null && is_string($value)) {
                if ($value[0] === '#') {
                    $value = $this->get("$type$value");
                } elseif (is_array($callable)) {
                    $object = $callable[0];
                    $dependencies = $this->dependencies[$object] ?? null;
                    $id = $dependencies[$name] ?? $dependencies[$type] ?? $type;

                    $value = $this->get($id[0] === '#' ? "$type$id" : $id);
                } else {
                    $value = $this->get($value);
                }
            }

            $args[] = $value;
        }

        return $callable(...$args);
    }
}
