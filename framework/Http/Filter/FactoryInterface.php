<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface FactoryInterface
{
    public function get(string $filter): mixed;
}