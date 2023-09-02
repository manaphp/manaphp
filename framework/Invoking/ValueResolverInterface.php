<?php
declare(strict_types=1);

namespace ManaPHP\Invoking;

use ReflectionParameter;

interface ValueResolverInterface
{
    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): mixed;
}