<?php
declare(strict_types=1);

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\TracerInterface;
use Psr\Container\ContainerInterface;

class TracerBootstrapper implements BootstrapperInterface
{
    #[Autowired] protected TracerInterface $tracer;

    #[Autowired] protected bool $enabled = true;

    public function bootstrap(ContainerInterface $container): void
    {
        if ($this->enabled) {
            $this->tracer->start();
        }
    }
}