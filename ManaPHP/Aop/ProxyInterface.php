<?php

namespace ManaPHP\Aop;

interface ProxyInterface
{
    /**
     * @return mixed
     */
    public function __getTarget();
}
