<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Data\DbInterface;
use ManaPHP\Di\Attribute\Inject;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    #[Inject] protected ContainerInterface $container;

    public function get($connection): DbInterface
    {
        return $this->container->get(DbInterface::class . '#' . $connection);
    }
}