<?php
declare(strict_types=1);

namespace ManaPHP\Html\Renderer\Engine;

use ManaPHP\Html\Renderer\EngineInterface;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get(string $engine): EngineInterface
    {
        return $this->container->get($engine);
    }
}