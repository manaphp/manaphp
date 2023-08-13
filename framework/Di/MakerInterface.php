<?php
declare(strict_types=1);

namespace ManaPHP\Di;

interface MakerInterface
{
    public function make(string $name, array $parameters = []): mixed;
}