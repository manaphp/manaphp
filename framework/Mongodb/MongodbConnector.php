<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb;

use ManaPHP\Di\Attribute\Autowired;
use Psr\Container\ContainerInterface;

class MongodbConnector implements MongodbConnectorInterface
{
    #[Autowired] protected ContainerInterface $container;

    public function get(string $name = 'default'): MongodbInterface
    {
        return $this->container->get(MongodbInterface::class . ($name === 'default' ? '' : "#$name"));
    }
}