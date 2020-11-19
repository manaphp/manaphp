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
    public function getInstance($class, $params = []);

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getShared($name);

    /**
     * @param string $old
     * @param mixed  $new
     *
     * @return static
     */
    public function inject($old, $new);
}