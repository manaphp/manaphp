<?php

namespace ManaPHP;

interface RedisInterface
{
    /**
     * @return \Redis
     */
    public function getMaster();

    /**
     * @return \Redis
     */
    public function getSlave();
}