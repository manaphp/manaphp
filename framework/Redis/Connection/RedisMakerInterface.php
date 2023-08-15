<?php
declare(strict_types=1);

namespace ManaPHP\Redis\Connection;

interface RedisMakerInterface
{
    public function make(string $class, array $parameters = []): mixed;
}