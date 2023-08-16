<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\Controller;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    #[Inject] protected ContainerInterface $container;

    public function get(string $controller): Controller
    {
        return $this->container->get($controller);
    }
}