<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Data\DbInterface;

interface ConnectorInterface
{
    public function get(string $connection): DbInterface;
}