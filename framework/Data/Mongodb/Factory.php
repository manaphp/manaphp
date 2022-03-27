<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Data\MongodbInterface;
use Psr\Container\ContainerInterface;

class Factory implements FactoryInterface
{
    protected ContainerInterface $container;

    public function get(string $connection): MongodbInterface
    {
        return $this->container->get(MongodbInterface::class . '#' . $connection);
    }
}