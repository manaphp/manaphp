<?php

namespace ManaPHP\Di;

interface Injectable
{
    /**
     * @param string $class
     * @param array  $params
     *
     * @return mixed
     */
    public function getNew($class, $params = []);

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getShared($name);

    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     *
     * @return static
     */
    public function setContainer($container);

    /**
     * @return \ManaPHP\Di\ContainerInterface
     */
    public function getContainer();

    /**
     * @param string $name
     * @param mixed  $target
     *
     * @return static
     */
    public function inject($name, $target);
}