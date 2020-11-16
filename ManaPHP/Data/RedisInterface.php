<?php

namespace ManaPHP\Data;

interface RedisInterface
{
    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function call($method, $arguments);

    /**
     * @return \Redis
     */
    public function getMaster();

    /**
     * @return \Redis
     */
    public function getSlave();
}