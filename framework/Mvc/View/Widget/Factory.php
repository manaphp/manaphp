<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Widget;

use ManaPHP\Mvc\View\WidgetInterface;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get(string $widget): WidgetInterface
    {
        return $this->container->get($widget);
    }
}