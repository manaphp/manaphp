<?php

namespace ManaPHP\Di;

interface Injectable
{
    /**
     * Sets the dependency injector
     *
     * @param \ManaPHP\DiInterface $di
     *
     * @return void
     */
    public function setDi($di);

    /**
     * Returns the internal dependency injector
     *
     * @return \ManaPHP\Di
     */
    public function getDi();

    /**
     * @param string $class
     * @param array  $params
     *
     * @return mixed
     */
    public function getInstance($class, $params = []);

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getShared($name);

    /**
     * @param string $old
     * @param string $new
     *
     * @return static
     */
    public function inject($old, $new);
}