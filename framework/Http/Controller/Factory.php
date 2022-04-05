<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

use ManaPHP\Http\Controller;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get(string $controller): Controller
    {
        return $this->container->get($controller);
    }
}