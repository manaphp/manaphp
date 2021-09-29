<?php

namespace ManaPHP\Aop\Aspect;

use ManaPHP\Helper\LocalFS;

class Provider extends \ManaPHP\Di\Provider
{
    public function boot($container)
    {
        foreach (LocalFS::glob('@app/Aspects/?*Aspect.php') as $item) {
            $aspect = 'App\Aspects\\' . basename($item, '.php');
            $container->get($aspect);
        }
    }
}