<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\EventSubscriberInterface;
use Psr\Container\ContainerInterface;

class FilterManager implements FilterManagerInterface
{
    #[Inject] protected EventSubscriberInterface $eventSubscriber;
    #[Inject] protected ContainerInterface $container;
    #[Inject] protected ConfigInterface $config;

    public function register(): void
    {
        $filters = $this->config->get('filters', []);

        foreach ($filters as $filter) {
            $this->eventSubscriber->addListener($this->container->get($filter));
        }
    }
}