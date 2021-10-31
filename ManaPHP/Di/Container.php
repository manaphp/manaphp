<?php

namespace ManaPHP\Di;

use Closure;
use ManaPHP\Event\Emitter;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidValueException;
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
    protected $properties = [];

    /**
     * @var \ManaPHP\Event\EmitterInterface
     */
    protected $emitter;

    /**
     * @param array $definitions
     */
    public function __construct($definitions = [])
    {
        $this->definitions = $definitions;
        $this->definitions['ManaPHP\Di\ContainerInterface'] = $this;
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
     * @param string $class
     *
     * @return array
     */
    protected function resolveProperties($class)
    {
        $rc = new ReflectionClass($class);
        $comment = $rc->getDocComment();

        $resolved = [];
        if (is_string($comment)) {
            if (preg_match_all('#@property-read\s+\\\\?([\w\\\\]+)\s+\\$(\w+)#m', $comment, $matches, PREG_SET_ORDER)
                > 0
            ) {
                foreach ($matches as list(, $type, $name)) {
                    if ($type === 'object') {
                        continue;
                    }
                    $resolved[$name] = $type;
                }
            }
        }

        $parent = get_parent_class($class);
        if ($parent !== false) {
            $resolved += $this->properties[$parent] ?? $this->resolveProperties($parent);
        }
        return $this->properties[$class] = $resolved;
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
        $resolved = $this->properties[$class] ?? $this->resolveProperties($class);

        if (($type = $resolved[$property] ?? null) === null) {
            throw new InvalidArgumentException('sss');
        }

        return $this->get($type);
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
                if ($this->has($type)) {
                    $value = $this->get($type);
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
