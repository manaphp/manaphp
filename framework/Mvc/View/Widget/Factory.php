<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Widget;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Mvc\View\WidgetInterface;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    #[Inject] protected ContainerInterface $container;

    public function get(string $widget): WidgetInterface
    {
        return $this->container->get($widget);
    }
}