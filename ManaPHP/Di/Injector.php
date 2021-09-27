<?php

namespace ManaPHP\Di;

class Injector implements InjectorInterface
{
    /**
     * @var array
     */
    protected $dependencies = [];

    /**
     * @var \ManaPHP\Di\ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     * @param array              $dependencies
     */
    public function __construct($container, $dependencies = [])
    {
        $this->container = $container;
        $this->dependencies = $dependencies;
    }

    public function get($name)
    {
        if (($dependency = $this->dependencies[$name] ?? null) !== null) {
            return $this->container->get($dependency);
        } else {
            return $this->container->get($name);
        }
    }

    public function has($name)
    {
        if (isset($this->dependencies[$name])) {
            return true;
        } else {
            return $this->container->has($name);
        }
    }

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function make($name, $parameters = [])
    {
        if (($dependency = $this->dependencies[$name] ?? null) !== null) {
            return $this->container->make($dependency, $parameters);
        } else {
            return $this->container->make($name, $parameters);
        }
    }
}