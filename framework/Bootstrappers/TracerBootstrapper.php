<?php
declare(strict_types=1);

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\TracerInterface;
use Psr\Container\ContainerInterface;

class TracerBootstrapper implements BootstrapperInterface
{
    #[Inject] protected TracerInterface $tracer;

    #[Value] protected bool $enabled = true;

    public function bootstrap(ContainerInterface $container): void
    {
        if ($this->enabled) {
            $this->tracer->start();
        }
    }
}