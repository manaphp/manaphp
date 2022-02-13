<?php
declare(strict_types=1);

namespace ManaPHP\Rpc;

interface RequestInterface
{
    public function has(string $name): bool;

    public function get(string $name, mixed $default): mixed;
}