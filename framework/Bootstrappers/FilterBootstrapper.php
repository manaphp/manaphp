<?php
declare(strict_types=1);

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\ListenerProviderInterface;
use Psr\Container\ContainerInterface;

class FilterBootstrapper implements BootstrapperInterface
{
    #[Inject] protected ListenerProviderInterface $listenerProvider;
    #[Inject] protected ConfigInterface $config;

    public function bootstrap(ContainerInterface $container): void
    {
        $filters = $this->config->get('filters', []);

        foreach ($filters as $filter) {
            $this->listenerProvider->add($container->get($filter));
        }
    }
}