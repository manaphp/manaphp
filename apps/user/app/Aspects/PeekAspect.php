<?php
namespace App\Aspects;

use ManaPHP\Aop\Aspect;
use ManaPHP\Aop\JoinPoint;
use ManaPHP\Identity;

class PeekAspect extends Aspect
{
    public function register()
    {

        $this->aop->addPointCut(Identity::class, 'setClaims', '$claims');
        $this->aop->addPointCuts([
            "ManaPHP\\Component",
            "ManaPHP\\Alias",
            "ManaPHP\\Event\\Manager",
            "ManaPHP\\Dotenv",
            "ManaPHP\\Configuration\\Configure",
            "ManaPHP\\Http\\RequestContext",
           "ManaPHP\\Http\\Request",
            "ManaPHP\\IdentityContext",
           "ManaPHP\\Identity",
            "ManaPHP\\Identity\\Adapter\\Jwt",
           "ManaPHP\\Security\\Crypt",
            "ManaPHP\\RouterContext",
            "ManaPHP\\Router",
            "App\\Router",
            "ManaPHP\\Router\\Route",
            "ManaPHP\\DispatcherContext",
            "ManaPHP\\Dispatcher",
            "ManaPHP\\Rest\\Controller",
            "App\\Controllers\\ControllerBase",
            "App\\Controllers\\TimeController",
            "ManaPHP\\Invoker",
            "ManaPHP\\Http\\ResponseContext",
            "ManaPHP\\Http\\Response"
        ], '*', function (JoinPoint $joinPoint) {
            $joinPoint->addBefore();
        });
    }
}