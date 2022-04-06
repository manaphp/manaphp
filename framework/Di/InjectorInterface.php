<?php
declare(strict_types=1);

namespace ManaPHP\Di;

interface InjectorInterface
{
    public function inject(object $object, string $property): mixed;
}