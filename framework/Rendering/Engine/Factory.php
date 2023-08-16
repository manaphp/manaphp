<?php
declare(strict_types=1);

namespace ManaPHP\Rendering\Engine;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Rendering\EngineInterface;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    #[Inject] protected ContainerInterface $container;

    public function get(string $engine): EngineInterface
    {
        return $this->container->get($engine);
    }
}