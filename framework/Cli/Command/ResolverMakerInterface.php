<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Command;

interface ResolverMakerInterface
{
    public function make(array $parameters): mixed;
}