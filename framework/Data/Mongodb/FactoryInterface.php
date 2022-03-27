<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Data\MongodbInterface;

interface FactoryInterface
{
    public function get(string $connection): MongodbInterface;
}