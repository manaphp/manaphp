<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Di\Attribute\Inject;
use Psr\Container\ContainerInterface;

class MongodbConnector implements MongodbConnectorInterface
{
    #[Inject] protected ContainerInterface $container;

    public function get(string $name = 'default'): MongodbInterface
    {
        return $this->container->get(MongodbInterface::class . ($name === 'default' ? '' : "#$name"));
    }
}