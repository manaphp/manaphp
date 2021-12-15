<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Logging\Logger\Adapter\File;

class LoggerFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
    {
        return $container->get(File::class);
    }
}