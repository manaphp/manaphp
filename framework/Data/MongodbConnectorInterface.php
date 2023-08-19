<?php
declare(strict_types=1);

namespace ManaPHP\Data;

interface MongodbConnectorInterface
{
    public function get(string $name = 'default'): MongodbInterface;
}