<?php

namespace ManaPHP\Aop;

interface JoinPointInterface
{
    /**
     * @param array $args
     *
     * @return mixed
     */
    public function invoke($args);
}