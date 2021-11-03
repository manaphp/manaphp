<?php

namespace ManaPHP\Http;

use ManaPHP\Di\FactoryInterface;
use ManaPHP\Http\Session\Adapter\File;

class SessionFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
        return $container->get(File::class);
    }
}