<?php
declare(strict_types=1);

namespace ManaPHP\Di\Property;

interface InjectorInterface
{
    public function inject(string $class, string $property): string;
}