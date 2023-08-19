<?php
declare(strict_types=1);

namespace ManaPHP\Data;

interface DbConnectorInterface
{
    public function get(string $name = 'default'): DbInterface;
}