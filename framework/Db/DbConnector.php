<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\Attribute\Inject;
use Psr\Container\ContainerInterface;

class DbConnector implements DbConnectorInterface
{
    #[Inject] protected ContainerInterface $container;

    public function get($name = 'default'): DbInterface
    {
        return $this->container->get(DbInterface::class . ($name === 'default' ? '' : "#$name"));
    }
}