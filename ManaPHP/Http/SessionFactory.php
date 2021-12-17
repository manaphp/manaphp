<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Http\Session\Adapter\File;

class SessionFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): mixed
    {
        return $container->get(File::class);
    }
}