<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

interface ThoseInterface
{
    public function get(string $class): Entity;
}