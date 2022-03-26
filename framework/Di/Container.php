<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use Closure;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Exception\MisuseException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class Container implements ContainerInterface, \Psr\Container\ContainerInterface
{
    protected array $definitions = [];
    protected array $instances = [];
    protected array $types = [];
    protected array $dependencies = [];

    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
        $this->definitions['ManaPHP\Di\ContainerInterface'] = $this;
        $this->definitions['Psr\Container\ContainerInterface'] = $this;
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

    public function make(string $class, array $parameters = [], ?string $id = null): mixed
    {
        if (is_string(($alias = $this->definition[$class] ?? null))) {
            return $this->make($alias, $parameters, $id);
        }

        $exists = false;
        /** @noinspection NotOptimalIfConditionsInspection */
        if (str_ends_with($class, 'Interface') && interface_exists($class)) {
            $prefix = substr($class, 0, -9);
            if (class_exists($prefix)) {
                $exists = true;
                $class = $prefix;
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
            return $factory->make($this, $id, $parameters);
        }

        $dependencies = [];
        foreach ($parameters as $key => $value) {
            if (is_string($key) && str_contains($key, '\\')) {
                if (!is_string($value)) {
                    $dependencyId = "$class.dependencies.$key" . ($id === null || $id === $class ? '' : ".$id");
                    $this->set($dependencyId, $value);
                    $value = "@$dependencyId";
                }
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
                        if (array_key_exists($type, $parameters)) {
                            unset($dependencies[$type]);
                        }
                    }
                }
            }

            if ($id !== null) {
                $this->instances[$id] = $id;
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

    public function get(string $id): mixed
    {
        if (($instance = $this->instances[$id] ?? null) !== null) {
            return $instance;
        }

        $definition = $this->definitions[$id] ?? $id;

        if (is_string($definition)) {
            if ($definition[0] === '@') {
                return $this->get(substr($definition, 1));
            } elseif ($definition[0] === '#') {
                return $this->get("@$id$definition");
            } elseif (str_contains($definition, '.')) {
                $glob = substr($definition, 0, strrpos($definition, '.')) . '.*';
                if (($definition2 = $this->definitions[$glob] ?? null) !== null) {
                    if (is_string($definition2) && is_subclass_of($definition2, FactoryInterface::class)) {
                        /** @var \ManaPHP\Di\FactoryInterface $factory */
                        $factory = new $definition2();
                        return $this->instances[$id] = $factory->make($this, $id);
                    } else {
                        return $this->get($glob);
                    }
                } else {
                    throw new NotFoundException("`$id` is not found");
                }
            } else {
                return $this->instances[$id] = $this->make($definition, [], $id);
            }
        } elseif ($definition instanceof Closure) {
            return $this->instances[$id] = $this->call($definition);
        } elseif (is_object($definition)) {
            return $this->instances[$id] = $definition;
        } elseif (is_array($definition)) {
            if (isset($definition['#class']) || isset($definition['#parameters'])) {
                $class = $definition['#class'] ?? $id;
                $parameters = $definition['#parameters'] ?? [];
            } else {
                if (($class = $definition['class'] ?? null) !== null) {
                    unset($definition['class']);
                } else {
                    $class = $id;
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

            return $this->instances[$id] = $this->make($class, $parameters, $id);
        } else {
            throw new NotSupportedException('not supported definition');
        }
    }

    protected function getTypes(string $class): array
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

    public function inject(object $target, string $property): mixed
    {
        $class = get_class($target);
        $types = $this->types[$class] ?? $this->getTypes($class);

        if (($type = $types[$property] ?? null) === null) {
            throw new MisuseException(['can\'t type-hint for `%s`', $property]);
        }

        return $this->get($this->dependencies[spl_object_id($target)][$type] ?? $type);
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

                if ($rType->isBuiltin()) {
                    $missing[] = $name;
                    continue;
                } else {
                    $type = $rType->getName();

                    if (array_key_exists($type, $parameters)) {
                        $value = $parameters[$type];
                        if (is_string($value)) {
                            $value = $this->get($value);
                        }
                    } elseif (is_array($callable)) {
                        $object = $callable[0];
                        $value = $this->get($this->dependencies[spl_object_id($object)][$type] ?? $type);
                    } else {
                        $value = $this->get($type);
                    }
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
