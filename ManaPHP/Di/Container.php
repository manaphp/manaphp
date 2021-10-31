<?php

namespace ManaPHP\Di;

use Closure;
use ManaPHP\Event\Emitter;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ReflectionClass;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected $definitions = [];

    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var \ManaPHP\Event\EmitterInterface
     */
    protected $emitter;

    /**
     * @var \ManaPHP\Di\ContainerInterface
     */
    protected static $default;

    /**
     * @param array $definitions
     */
    public function __construct($definitions = [])
    {
        if (self::$default === null) {
            self::$default = $this;
        }

        $this->definitions = $definitions;
        $this->definitions['container'] = $this;
    }

    /**
     * @return static
     */
    public static function getDefault()
    {
        return self::$default;
    }

    /**
     * @param string $event
     * @param callable $handler
     *
     * @return static
     */
    public function on($event, $handler)
    {
        if ($this->emitter === null) {
            $this->emitter = new Emitter();
        }

        $this->emitter->on($event, $handler);

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $definition
     *
     * @return static
     */
    public function set($name, $definition)
    {
        if (isset($this->instances[$name])) {
            throw new MisuseException(['it\'s too late to set(): `%s` instance has been created', $name]);
        }

        $this->definitions[$name] = $definition;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function remove($name)
    {
        unset($this->definitions[$name], $this->instances[$name]);

        return $this;
    }

    /**
     * @param string $class
     * @param array  $parameters
     *
     * @return mixed
     */
    public function make($class, $parameters = [])
    {
        if (is_string(($alias = $this->definition[$class] ?? null))) {
            return $this->make($alias, $parameters);
        }

        $exists = false;
        if (str_ends_with($class, 'Interface') && interface_exists($class)) {
            if (class_exists($sub = substr($class, 0, -9))) {
                $exists = true;
                $class = (string)$sub;
            }
        } elseif (class_exists($class)) {
            $exists = true;
        }

        if (!$exists) {
            throw new InvalidValueException(
                ['`%s` component cannot be resolved: `%s` class is not exists', $class, $class]
            );
        }

        if (method_exists($class, '__construct')) {
            $rc = new ReflectionClass($class);

            $instance = $rc->newInstanceWithoutConstructor();

            if ($instance instanceof Injectable) {
                $instance->setContainer($this);
            }

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $this->call([$instance, '__construct'], $parameters);
        } else {
            $instance = new $class();

            if ($instance instanceof Injectable) {
                $instance->setContainer($this);
            }
        }

        return $instance;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        if (($instance = $this->instances[$name] ?? null) !== null) {
            return $instance;
        }

        $definition = $this->definitions[$name] ?? $name;

        if (is_string($definition)) {
            if ($definition[0] === '@') {
                return $this->get(substr($definition, 1));
            } else {
                return $this->instances[$name] = $this->make($definition);
            }
        } elseif ($definition instanceof Closure) {
            return $this->instances[$name] = $this->call($definition);
        } elseif (is_object($definition)) {
            return $this->instances[$name] = $definition;
        } elseif (is_array($definition)) {
            $parameters = $definition['#parameters'] ?? [];
            $definition = $definition['#class'] ?? $name;
            return $this->instances[$name] = $this->make($definition, $parameters);
        } else {
            throw new MisuseException('not supported definition');
        }
    }

    /**
     * @param object $target
     * @param string $property
     *
     * @return mixed
     */
    public function inject($target, $property)
    {
        $propertyResolver = $this->get(PropertyResolverInterface::class);
        $resolved = $propertyResolver->resolve(get_class($target), $property);
        return $this->get($resolved);
    }

    /**
     * @return array
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getDefinition($name)
    {
        return $this->definitions[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getInstances()
    {
        return $this->instances;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        if (isset($this->instances[$name])) {
            return true;
        } elseif (isset($this->definitions[$name])) {
            return true;
        } elseif (!str_contains($name, '\\')) {
            return false;
        } elseif (str_ends_with($name, 'Interface') && interface_exists($name)) {
            return class_exists(substr($name, 0, -9));
        } elseif (class_exists($name)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param callable $callable
     * @param array    $parameters
     *
     * @return mixed
     */
    public function call($callable, $parameters = [])
    {
        $invoker = $this->get(InvokerInterface::class);
        return $invoker->call($this, $callable, $parameters);
    }
}
