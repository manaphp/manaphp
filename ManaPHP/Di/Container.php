<?php

namespace ManaPHP\Di;

use Closure;
use ManaPHP\Event\Emitter;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ReflectionClass;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected $providers = [];

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
     * @param array $providers
     */
    public function __construct($providers = [])
    {
        if (self::$default === null) {
            self::$default = $this;
        }

        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
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
     * @param string $className
     *
     * @return string
     */
    protected function completeClassName($name, $className)
    {
        if (isset($this->definitions[$name])) {
            $definition = $this->definitions[$name];
        } else {
            return $className;
        }

        if (is_string($definition)) {
            if ($pos = strrpos($definition, '\\')) {
                return substr($definition, 0, $pos + 1) . ucfirst($className);
            } else {
                return $className;
            }
        } elseif (is_array($definition) && isset($definition['class'])) {
            if ($pos = strrpos($definition['class'], '\\')) {
                return substr($definition['class'], 0, $pos + 1) . ucfirst($className);
            } else {
                return $className;
            }
        } else {
            return $className;
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function inferClassName($name)
    {
        $definition = null;
        if (isset($this->definitions[$name])) {
            $definition = $this->definitions[$name];
        } elseif (str_contains($name, '\\')) {
            $definition = $name;
        } elseif ($pos = strrpos($name, '_')) {
            $maybe = substr($name, $pos + 1);
            if (isset($this->definitions[$maybe])) {
                $definition = $this->definitions[$maybe];
            } elseif ($pos = strpos($name, '_')) {
                $maybe = substr($name, 0, $pos);
                if (isset($this->definitions[$maybe])) {
                    $definition = $this->definitions[$maybe];
                }
            }
        } elseif (preg_match('#^(.+)([A-Z].+?)$#', $name, $match)) {
            $maybe = lcfirst($match[2]);
            $definition = $this->definitions[$maybe] ?? null;
        }

        if ($definition === null) {
            throw new InvalidValueException(['`%s` definition is invalid: missing class field', $name]);
        } elseif (is_string($definition)) {
            return $definition[0] === '@' ? $this->inferClassName(substr($definition, 1)) : $definition;
        } else {
            return $definition['class'];
        }
    }

    /**
     * Registers an "always shared" component in the components container
     *
     * @param string $name
     * @param mixed  $definition
     *
     * @return static
     */
    public function set($name, $definition)
    {
        if (isset($this->instances[$name])) {
            throw new MisuseException(['it\'s too late to setShared(): `%s` instance has been created', $name]);
        }

        if (is_string($definition)) {
            if ($definition[0] === '@') {
                null;
            } elseif (str_contains($definition, '/') || preg_match('#^[\w\\\\]+$#', $definition) !== 1) {
                $definition = ['class' => $this->inferClassName($name), $definition];
            } elseif (!str_contains($definition, '\\')) {
                $definition = $this->completeClassName($name, $definition);
            }
        } elseif (is_array($definition)) {
            if (isset($definition['class'])) {
                if (!str_contains($definition['class'], '\\')) {
                    $definition['class'] = $this->completeClassName($name, $definition['class']);
                }
            } elseif (isset($definition[0]) && count($definition) !== 1) {
                if (!str_contains($definition[0], '\\')) {
                    $definition[0] = $this->completeClassName($name, $definition[0]);
                }
            } else {
                $definition['class'] = $this->inferClassName($name);
            }
        } elseif ($definition instanceof Closure) {
            null;
        } elseif (is_object($definition)) {
            $this->instances[$name] = $definition;
        } else {
            throw new NotSupportedException(['`:definition` definition is unknown', 'definition' => $name]);
        }

        $this->definitions[$name] = $definition;

        return $this;
    }

    /**
     * @param string $providers
     *
     * @return static
     */
    public function addProvider($provider)
    {
        $this->providers[] = $provider;
        /** @var \ManaPHP\Di\ProviderInterface $instance */
        $instance = new $provider();
        /** @noinspection AdditionOperationOnArraysInspection */
        $this->definitions += $instance->getdefinitions();

        return $this;
    }

    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Removes a component in the components container
     *
     * @param string $name
     *
     * @return static
     */
    public function remove($name)
    {
        unset($this->definitions[$name], $this->instances[$name], $this->{$name});

        return $this;
    }

    /**
     * Resolves the component based on its configuration
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function make($name, $parameters = [])
    {
        $definition = $this->definitions[$name] ?? $name;

        if ($parameters && !array_key_exists(0, $parameters)) {
            $parameters = [$parameters];
        }

        if (is_string($definition)) {
            return $this->makeInternal($name, $definition, $parameters);
        } elseif ($definition instanceof Closure) {
            $instance = $definition(...$parameters);
        } elseif (is_object($definition)) {
            $instance = $definition;
        } else {
            throw new NotSupportedException(['`%s` component cannot be resolved', $name]);
        }

        if ($instance instanceof Injectable) {
            $instance->setContainer($this);
        }

        return $instance;
    }

    /**
     * @param string $name
     * @param mixed  $instance
     *
     * @return mixed
     */
    protected function setInternal($name, $instance)
    {
        if ($this->emitter !== null) {
            $instance = $this->emitter->emit('resolved', $instance) ?? $instance;
        }

        $this->instances[$name] = $instance;

        return $instance;
    }

    /**
     * @param string $name
     * @param string $class
     * @param array  $parameters
     *
     * @return mixed
     */
    protected function makeInternal($name, $class, $parameters)
    {
        if (!class_exists($class)) {
            throw new InvalidValueException(
                ['`%s` component cannot be resolved: `%s` class is not exists', $name, $class]
            );
        }

        if (method_exists($class, '__construct')) {
            $rc = new ReflectionClass($class);

            $instance = $rc->newInstanceWithoutConstructor();
            $resolved = $this->setInternal($name, $instance);

            if ($instance instanceof Injectable) {
                $instance->setContainer($this, $instance);
            }

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $instance->__construct(...$parameters);
        } else {
            $instance = new $class(...$parameters);
            $resolved = $this->setInternal($name, $instance);

            if ($instance instanceof Injectable) {
                $instance->setContainer($this, $resolved ?? $instance);
            }
        }

        return $resolved;
    }

    /**
     * Resolves a component, the resolved component is stored in the DI, subsequent requests for this component will
     * return the same instance
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        if ($instance = $this->instances[$name] ?? null) {
            return $instance;
        }

        $definition = $this->definitions[$name] ?? $name;

        if (is_string($definition)) {
            if ($definition[0] === '@') {
                return $this->setInternal($name, $this->get(substr($definition, 1)));
            }
            $parameters = [];
        } elseif ($definition instanceof Closure) {
            $instance = $definition();
            if ($instance instanceof Injectable) {
                $instance->setContainer($this);
            }

            return $this->setInternal($name, $instance);
        } elseif (isset($definition['class'])) {
            $parameters = $definition;
            $definition = $definition['class'];
            unset($parameters['class']);
        } elseif (isset($definition[0])) {
            $parameters = $definition;
            $definition = $definition[0];
            unset($parameters[0]);
        } else {
            $parameters = [];
        }

        if ($parameters && !array_key_exists(0, $parameters)) {
            $parameters = [$parameters];
        }

        if (!is_string($definition)) {
            throw new NotSupportedException(['`%s` component implement type is not supported', $name]);
        }

        $definition = $this->definitions[$definition] ?? $definition;

        $instance = $this->makeInternal($name, $definition, $parameters);
        $this->instances[$name] = $instance;

        return $instance;
    }

    /**
     * @param string $pattern
     *
     * @return array
     */
    public function getDefinitions($pattern = null)
    {
        if ($pattern === null) {
            return $this->definitions;
        } else {
            $definitions = [];
            foreach ($this->definitions as $name => $definition) {
                if (fnmatch($pattern, $name)) {
                    $definitions[$name] = $definition;
                }
            }
            return $definitions;
        }
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
     * Check whether the DI contains a component by a name
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->definitions[$name]);
    }
}
