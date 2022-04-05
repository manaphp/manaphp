<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Command;

use ManaPHP\Cli\Command;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get(string $command): Command
    {
        return $this->container->get($command);
    }
}