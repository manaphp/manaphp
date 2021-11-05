<?php

namespace ManaPHP\Di;

use Closure;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Exception\MisuseException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;

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
     * @var array
     */
    protected $types = [];

    /**
     * @var array
     */
    protected $dependencies = [];

    /**
     * @param array $definitions
     */
    public function __construct($definitions = [])
    {
        $this->definitions = $definitions;
        $this->definitions['ManaPHP\Di\ContainerInterface'] = $this;
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
     * @param string $name
     *
     * @return mixed
     */
    public function make($class, $parameters = [], $name = null)
    {
        if (is_string(($alias = $this->definition[$class] ?? null))) {
            return $this->make($alias, $parameters, $name);
        }

        $exists = false;
        if (str_ends_with($class, 'Interface') && interface_exists($class)) {
            $prefix = substr($class, 0, -9);
            if (class_exists($prefix)) {
                $exists = true;
                $class = (string)$prefix;
            } elseif (class_exists($factory = $prefix . 'Factory')) {
                $exists = true;
                $class = $factory;
            }
        } elseif (class_exists($class)) {
            $exists = true;
        }

        if (!$exists) {
            throw new NotFoundException(['`%s` class is not exists', $class]);
        }

        if (is_subclass_of($class, FactoryInterface::class)) {
            /** @var \ManaPHP\Di\FactoryInterface $factory */
            $factory = new $class();
            return $factory->make($this, $name, $parameters);
        }

        $dependencies = [];
        foreach ($parameters as $key => $value) {
            if (is_string($key) && str_contains($key, '\\')) {
                $dependencies[$key] = $value;
            }
        }

        if (method_exists($class, '__construct')) {
            $rClass = new ReflectionClass($class);

            $instance = $rClass->newInstanceWithoutConstructor();

            if ($instance instanceof Injectable) {
                $instance->setContainer($this);
            }

            if ($dependencies !== []) {
                $rMethod = $rClass->getMethod('__construct');
                foreach ($rMethod->getParameters() as $rParameter) {
                    if ($rParameter->hasType() && !($rType = $rParameter->getType())->isBuiltin()) {
                        $type = $rType->getName();
                        if (isset($dependencies[$type])) {
                            unset($parameters[$type]);
                            $parameters[$rParameter->getName()] = $this->get($dependencies[$type]);
                        } else {
                            $parameters[$rParameter->getName()] = $this->get($type);
                        }
                    }
                }
            }

            $this->call([$instance, '__construct'], $parameters);
        } else {
            $instance = new $class();

            if ($instance instanceof Injectable) {
                $instance->setContainer($this);
            }
        }

        if ($dependencies !== []) {
            $this->dependencies[spl_object_id($instance)] = $dependencies;
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
            } elseif (str_contains($definition, '.')) {
                $glob = substr($definition, 0, strrpos($definition, '.')) . '.*';
                if (($definition2 = $this->definitions[$glob] ?? null) !== null) {
                    if (is_string($definition2) && is_subclass_of($definition2, FactoryInterface::class)) {
                        /** @var \ManaPHP\Di\FactoryInterface $factory */
                        $factory = new $definition2();
                        return $this->instances[$name] = $factory->make($this, $name);
                    } else {
                        return $this->get($glob);
                    }
                } else {
                    throw new NotFoundException("`$name` is not found");
                }
            } else {
                return $this->instances[$name] = $this->make($definition, [], $name);
            }
        } elseif ($definition instanceof Closure) {
            return $this->instances[$name] = $this->call($definition);
        } elseif (is_object($definition)) {
            return $this->instances[$name] = $definition;
        } elseif (is_array($definition)) {
            if (isset($definition['#class']) || isset($definition['#parameters'])) {
                $class = $definition['#class'] ?? $name;
                $parameters = $definition['#parameters'] ?? [];
            } else {
                if (($class = $definition['class'] ?? null) !== null) {
                    unset($definition['class']);
                } else {
                    $class = $name;
                }

                $parameters = [];
                $options = [];
                foreach ($definition as $k => $v) {
                    if (is_int($k) || str_contains($k, '\\')) {
                        $parameters[$k] = $v;
                    } else {
                        $options[$k] = $v;
                    }
                }

                if ($options !== []) {
                    $parameters['options'] = $options;
                }
            }

            return $this->instances[$name] = $this->make($class, $parameters, $name);
        } else {
            throw new MisuseException('not supported definition');
        }
    }

    /**
     * @param string $class
     *
     * @return array
     */
    protected function getTypes($class)
    {
        $rClass = new ReflectionClass($class);
        $comment = $rClass->getDocComment();

        $types = [];
        if (is_string($comment)) {
            if (preg_match_all('#@property-read\s+\\\\?([\w\\\\]+)\s+\\$(\w+)#m', $comment, $matches, PREG_SET_ORDER)
                > 0
            ) {
                foreach ($matches as list(, $type, $name)) {
                    if ($type === 'object') {
                        continue;
                    }
                    $types[$name] = $type;
                }
            }
        }

        $parent = get_parent_class($class);
        if ($parent !== false) {
            $types += $this->types[$parent] ?? $this->getTypes($parent);
        }
        return $this->types[$class] = $types;
    }

    /**
     * @param object $target
     * @param string $property
     *
     * @return mixed
     */
    public function inject($target, $property)
    {
        $class = get_class($target);
        $types = $this->types[$class] ?? $this->getTypes($class);

        if (($type = $types[$property] ?? null) === null) {
            throw new MisuseException(['can\'t type-hint for `%s`', $property]);
        }

        return $this->get($this->dependencies[spl_object_id($target)][$type] ?? $type);
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
        } elseif (str_contains($name, '.')) {
            $glob = substr($name, 0, strrpos($name, '.')) . '.*';
            return isset($this->definitions[$glob]);
        } else {
            return interface_exists($name) || class_exists($name);
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
        if (is_array($callable)) {
            $rFunction = new ReflectionMethod($callable[0], $callable[1]);
        } else {
            $rFunction = new ReflectionFunction($callable);
        }

        $missing = [];
        $args = [];
        foreach ($rFunction->getParameters() as $position => $rParameter) {
            $name = $rParameter->getName();

            if (array_key_exists($position, $parameters)) {
                $value = $parameters[$position];
            } elseif (array_key_exists($name, $parameters)) {
                $value = $parameters[$name];
            } elseif ($rParameter->isDefaultValueAvailable()) {
                $value = $rParameter->getDefaultValue();
            } elseif ($rParameter->hasType()) {
                $rType = $rParameter->getType();
                $type = $rType->getName();
                if (!$rType->isBuiltin()) {
                    if (is_array($callable)) {
                        $object = $callable[0];
                        $value = $this->get($this->dependencies[spl_object_id($object)][$type] ?? $type);
                    } else {
                        $value = $this->get($type);
                    }
                } else {
                    $missing[] = $name;
                    continue;
                }
            } else {
                $missing[] = $name;
                continue;
            }

            $args[] = $value;
        }

        if ($missing) {
            throw new MissingFieldException(implode(",", $missing));
        }

        return $callable(...$args);
    }
}
