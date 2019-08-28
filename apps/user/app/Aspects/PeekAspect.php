<?php
namespace App\Aspects;

use ManaPHP\Aop\Aspect;
use ManaPHP\Aop\JoinPoint;
use ManaPHP\Db;

class PeekAspect extends Aspect
{
    public function register()
    {
//        $this->aop::pointcutMethod(Db::class, 'fetchAll')->addAfter(function (JoinPoint $joinPoint){
//            var_dump($joinPoint->args);
//        });
    }
}