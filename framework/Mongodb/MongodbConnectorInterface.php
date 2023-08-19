<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb;

interface MongodbConnectorInterface
{
    public function get(string $name = 'default'): MongodbInterface;
}