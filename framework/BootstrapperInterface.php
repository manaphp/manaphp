<?php
declare(strict_types=1);

namespace ManaPHP;

use Psr\Container\ContainerInterface;

interface BootstrapperInterface
{
    public function bootstrap(ContainerInterface $container): void;
}