<?php
declare(strict_types=1);

namespace ManaPHP\Db;

interface DbConnectorInterface
{
    public function get(string $name = 'default'): DbInterface;
}