<?php
declare(strict_types=1);

namespace ManaPHP\Invoking;

interface ValueResolverInterface
{
    public function resolve(?string $type, string $name): mixed;
}