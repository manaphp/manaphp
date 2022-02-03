<?php
declare(strict_types=1);

namespace ManaPHP\Rpc;

interface ClientInterface
{
    public function invoke(string $method, array $params = [], array $options = []): mixed;
}