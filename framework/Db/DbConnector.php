<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\Attribute\Autowired;
use Psr\Container\ContainerInterface;

class DbConnector implements DbConnectorInterface
{
    #[Autowired] protected ContainerInterface $container;

    public function get(string $name = 'default'): DbInterface
    {
        return $this->container->get(DbInterface::class . ($name === 'default' ? '' : "#$name"));
    }
}