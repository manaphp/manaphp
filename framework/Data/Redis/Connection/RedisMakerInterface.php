<?php
declare(strict_types=1);

namespace ManaPHP\Data\Redis\Connection;

interface RedisMakerInterface
{
    public function make(string $class, array $parameters = []): mixed;
}