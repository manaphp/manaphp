<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get(string $filter): mixed
    {
        return $this->container->get($filter);
    }
}