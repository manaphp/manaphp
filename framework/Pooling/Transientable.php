<?php
declare(strict_types=1);

namespace ManaPHP\Pooling;

interface Transientable
{
    public function getTransientWrapper(string $type = 'default'): Transient;

    public function transientCall(object $instance, string $method, array $arguments): mixed;
}