<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Amqp;

interface EngineInterface
{
    public function call(string $exchange, string $routing_key, string $body, array $properties, array $options): mixed;
}