<?php

namespace ManaPHP\Logging;

use ManaPHP\Di\FactoryInterface;
use ManaPHP\Logging\Logger\Adapter\File;

class LoggerFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
        return $container->get(File::class);
    }
}