<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

interface ResolverMakerInterface
{
    public function make(array $parameters): mixed;
}