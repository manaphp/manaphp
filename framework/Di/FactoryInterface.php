<?php
declare(strict_types=1);

namespace ManaPHP\Di;

interface FactoryInterface
{
    public function make(string $name, array $parameters = []): mixed;
}