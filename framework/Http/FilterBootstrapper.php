<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\BootstrapperInterface;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\ListenerProviderInterface;
use Psr\Container\ContainerInterface;

class FilterBootstrapper implements BootstrapperInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected ConfigInterface $config;

    #[Autowired] protected array $filters = [];

    public function bootstrap(ContainerInterface $container): void
    {
        foreach ($this->filters as $filter) {
            $this->listenerProvider->add($container->get($filter));
        }
    }
}