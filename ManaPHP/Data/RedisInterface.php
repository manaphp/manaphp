<?php

namespace ManaPHP\Data;

use ManaPHP\Pool\Transientable;

interface RedisInterface extends Transientable
{
    /**
     * @param string                         $method
     * @param array                          $arguments
     * @param \ManaPHP\Data\Redis\Connection $connection
     *
     * @return mixed
     */
    public function call($method, $arguments, $connection = null);
}