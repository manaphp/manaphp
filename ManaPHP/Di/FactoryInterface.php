<?php
declare(strict_types=1);

namespace ManaPHP\Di;

interface FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object;
}