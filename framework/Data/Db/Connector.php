<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Data\DbInterface;
use ManaPHP\Di\Attribute\Inject;
use Psr\Container\ContainerInterface;

class Connector implements ConnectorInterface
{
    #[Inject] protected ContainerInterface $container;

    public function get($connection): DbInterface
    {
        return $this->container->get(DbInterface::class . '#' . $connection);
    }
}