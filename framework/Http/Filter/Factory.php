<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

use ManaPHP\Di\Attribute\Inject;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    #[Inject] protected ContainerInterface $container;

    public function get(string $filter): mixed
    {
        return $this->container->get($filter);
    }
}