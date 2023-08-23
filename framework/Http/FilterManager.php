<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\ListenerProviderInterface;
use Psr\Container\ContainerInterface;

class FilterManager implements FilterManagerInterface
{
    #[Inject] protected ListenerProviderInterface $listenerProvider;
    #[Inject] protected ContainerInterface $container;
    #[Inject] protected ConfigInterface $config;

    public function register(): void
    {
        $filters = $this->config->get('filters', []);

        foreach ($filters as $filter) {
            $this->listenerProvider->add($this->container->get($filter));
        }
    }
}