<?php

namespace App\Aspects;

use ManaPHP\Aop\Aspect;
use ManaPHP\Aop\JoinPoint;

class PeekAspect extends Aspect
{
    public function __construct()
    {
        return;

        $this->aopManager->before(
            '*::*', function (JoinPoint $joinPoint) {
            var_dump(get_class($joinPoint->getTarget()) . '::' . $joinPoint->getMethod());
        }
        );
    }
}