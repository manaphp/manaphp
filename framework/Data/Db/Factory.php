<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Data\DbInterface;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get($connection): DbInterface
    {
        return $this->container->get(DbInterface::class . '#' . $connection);
    }
}