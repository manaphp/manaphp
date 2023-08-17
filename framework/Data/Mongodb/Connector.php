<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Data\MongodbInterface;
use ManaPHP\Di\Attribute\Inject;
use Psr\Container\ContainerInterface;

class Connector implements ConnectorInterface
{
    #[Inject] protected ContainerInterface $container;

    public function get(string $connection): MongodbInterface
    {
        return $this->container->get(MongodbInterface::class . '#' . $connection);
    }
}