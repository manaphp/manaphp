<?php

declare(strict_types=1);

namespace ManaPHP\Di;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config as ConfigAttribute;
use ManaPHP\Di\Attribute\InterceptorInterface;
use ManaPHP\Di\Event\FactoryObjectInjected;
use ManaPHP\Di\Event\SingletonCreated;
use ManaPHP\Exception\MisuseException;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use function array_key_exists;
use function class_exists;
use function compact;
use function explode;
use function interface_exists;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;
use function preg_match;
use function str_contains;
use function str_ends_with;
use function strpos;
use function strrpos;
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
            throw new MisuseException('Cannot set definition for "{id}" because the instance has already been created.', ['id' => $id]);
        }

        if ($definition instanceof FactoryInterface) {
            $this->instances[FactoryInterface::class . "#$id"] = $definition;
            $definition->register($id, $this);
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

    protected function getLazyTypeName(ReflectionUnionType $ruType): ?string
    {
        $lazy = false;
        $type = null;
        foreach ($ruType->getTypes() as $rType) {
            if ($rType->getName() === Lazy::class) {
                $lazy = true;
            } else {
                $type = $rType->getName();
            }
        }

        return $lazy ? $type : null;
    }

    protected function getInjectedObject(string $type, string $name, ?string $value): object
    {
        if ($value !== null) {
            if (str_contains($value, '#')) {
                $this->dispatchEvent(new FactoryObjectInjected($type, $name, $value[0] === '#' ? "$type$value" : $value));
            }
            $value = $this->get($value[0] === '#' ? "$type$value" : $value);
        } else {
            $alias = "$type#$name";
            if (isset($this->definitions[$alias])) {
                $this->dispatchEvent(new FactoryObjectInjected($type, $name, $alias));
                $value = $this->get($alias);
            } else {
                $value = $this->get($type);
            }
        }

        return $value;
    }

    protected function injectObject(ReflectionProperty $property, object $object, array $parameters): void
    {
        $name = $property->getName();

        if (($value = $parameters[$name] ?? null) === null || is_string($value)) {
            if (($rType = $property->getType()) === null) {
                throw new Exception('Property "{class}::{name}" has no type declaration.', ['class' => $object::class, 'name' => $name]);
            }

            if ($rType instanceof ReflectionUnionType) {
                $type = $this->getLazyTypeName($rType);
                $value = new LazyPropertyProxy($this, $property, $object, $type, $value);
            } elseif (!is_object($value)) {
                $value = $this->getInjectedObject($rType->getName(), $name, $value);
            }
        }

        if (PHP_VERSION_ID < 80100) {
            /** @noinspection PhpExpressionResultUnusedInspection */
            $property->setAccessible(true);
        }
        $property->setValue($object, $value);
    }

    protected function injectNoValue(ReflectionProperty $property, $object): void
    {
        $rType = $property->getType();

        if ($rType !== null && $rType->allowsNull()) {
            if (PHP_VERSION_ID < 80100) {
                /** @noinspection PhpExpressionResultUnusedInspection */
                $property->setAccessible(true);
            }
            $property->setValue($object, null);
        } else {
            throw new Exception(
                'The property value of "{class}::${name}" is not provided.', ['class' => $property->class, 'name' => $property->getName()]
            );
        }
    }

    protected function injectValue(ReflectionProperty $property, object $object, array $parameters): void
    {
        $name = $property->getName();

        if (array_key_exists($name, $parameters)) {
            if (PHP_VERSION_ID < 80100) {
                /** @noinspection PhpExpressionResultUnusedInspection */
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
            $property->setValue($object, $parameters[$name]);
        } elseif (($config = $this->get(ConfigInterface::class))->has($name)) {
            if (PHP_VERSION_ID < 80100) {
                /** @noinspection PhpExpressionResultUnusedInspection */
                $property->setAccessible(true);
            }
            $property->setValue($object, $config->get($name));
        } elseif (!$property->hasDefaultValue() && $property->hasType()) {
            $this->injectNoValue($property, $object);
        }
    }

    public function injectProperties(object $object, ReflectionClass $rClass, array $parameters): void
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
                        'The type of "{class}::{name}" is missing.', ['class' => $object::class, 'name' => $property->getName()]
                    );
                }
            } elseif (isset($attributes[ConfigAttribute::class])) {
                $this->injectConfig($property, $object, $parameters);
            }
        }
    }

    protected function makeInternal(string $name, array $parameters = [], ?string $id = null): object
    {
        $rClass = new ReflectionClass($name);
        if (method_exists($name, '__construct')) {
            $instance = $rClass->newInstanceWithoutConstructor();

            if ($id !== null) {
                $this->instances[$id] = $instance;
            }

            $this->injectProperties($instance, $rClass, $parameters);

            $this->call([$instance, '__construct'], $parameters, false);
        } else {
            $instance = new $name();

            if ($id !== null) {
                $this->instances[$id] = $instance;
            }

            $this->injectProperties($instance, $rClass, $parameters);
        }

        return $instance;
    }

    public function make(string $name, array $parameters = [], ?string $id = null): mixed
    {
        while (is_string($definition = $this->definitions[$name] ?? null) && !str_contains($definition, '#')) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $name = $definition;
        }

        if (preg_match('#^[\w\\\\]+$#', $name) !== 1) {
            throw new NotFoundException('The class or interface "{name}" could not be found.', ['name' => $name]);
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
            throw new NotFoundException('The class or interface "{name}" does not exist.', ['name' => $name]);
        }

        if (method_exists($name, '__invoke')) {
            if (($object = $this->instances[$name] ?? null) === null) {
                $object = $this->makeInternal($name, [], $name);
            }
            $instance = $this->call([$object, '__invoke'], compact('parameters', 'id'), false);
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
                throw new Exception('The definition for "{id}" could not be found.', ['id' => $id]);
            }

            $instance = $this->make($id, [], $id);
            if (class_exists($id, false) && interface_exists($id . 'Interface', false)) {
                unset($this->instances[$id]);
                throw new MisuseException('Please use "{id}Interface" instead of "{id}" for autowiring.', ['id' => $id]);
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
            throw new Exception('The definition for "{id}" is not supported.', ['id' => $id]);
        } elseif (str_contains($definition, '#')) {
            if (str_contains($id, '#')) {
                list($type,) = explode('#', $id);
            } else {
                $type = $id;
            }
            return $this->instances[$id] = $this->get($definition[0] === '#' ? "$type$definition" : $definition);
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
        } elseif (str_contains($id, '#')) {
            return isset($this->definitions[$id]);
        } elseif (isset($this->definitions[$id])) {
            return true;
        } elseif (str_contains($id, '.')) {
            $glob = substr($id, 0, strrpos($id, '.')) . '.*';
            return isset($this->definitions[$glob]);
        } else {
            return interface_exists($id) || class_exists($id);
        }
    }

    public function call(callable $callable, array $parameters = [], bool $useInterceptor = true): mixed
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
                $value = $parameters[$type] ?? null;
            } else {
                $signature = is_array($callable)
                    ? $callable[0]::class . '::' . $callable[1]
                    : $rFunction->getName();
                throw new Exception('Cannot autowire argument "{name}" of method {signature}() because it has no type hint or default value.', ['name' => $name, 'signature' => $signature]);
            }

            if ($type !== null && !is_object($value)) {
                $value = $this->getInjectedObject($type, $name, $value);
            }

            $args[] = $value;
        }

        if ($useInterceptor && $rFunction instanceof ReflectionMethod) {
            $interceptors = $this->getInterceptors($rFunction);

            /** @var InterceptorInterface $interceptor */
            foreach ($interceptors as $interceptor) {
                $interceptor->preHandle($rFunction);
            }
            $return = $callable(...$args);
            foreach ($interceptors as $interceptor) {
                $interceptor->postHandle($rFunction, $return);
            }
            return $return;
        } else {
            return $callable(...$args);
        }
    }

    protected function getInterceptors(ReflectionMethod $rMethod): array
    {
        $interceptors = [];

        $attributes = $rMethod->getAttributes(InterceptorInterface::class, ReflectionAttribute::IS_INSTANCEOF);
        foreach ($attributes as $attribute) {
            $interceptors[] = $this->make($attribute->getName(), $attribute->getArguments());
        }

        return $interceptors;
    }
}
