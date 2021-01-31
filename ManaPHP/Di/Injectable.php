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
     * @param \ManaPHP\DiInterface $di
     *
     * @return static
     */
    public function setDi($di);

    /**
     * @return \ManaPHP\DiInterface
     */
    public function getDi();

    /**
     * @param string $name
     * @param mixed  $target
     *
     * @return static
     */
    public function inject($name, $target);
}