<?php
declare(strict_types=1);

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\ListenerProviderInterface;
use Psr\Container\ContainerInterface;

class FilterBootstrapper implements BootstrapperInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected ConfigInterface $config;

    public function bootstrap(ContainerInterface $container): void
    {
        $filters = $this->config->get('filters', []);

        foreach ($filters as $filter) {
            $this->listenerProvider->add($container->get($filter));
        }
    }
}