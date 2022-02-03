<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Ws\Client\Message;

interface ClientInterface
{
    public function on(string $event, callable $handler): static;

    public function getEndpoint(): string;

    public function setEndpoint(string $endpoint): static;

    public function request(string $message, ?float $timeout = null): Message;

    public function subscribe(callable $handler, int $keepalive = 60): void;
}