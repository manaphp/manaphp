<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Client;

interface EngineInterface
{
    public function setEndpoint(string $endpoint): static;

    public function getEndpoint(): string;

    public function send(int $op_code, string $data, float $timeout): void;

    public function isReceiveReady(float $timeout): bool;

    public function receive(?float $timeout = null): Message;

    public function close(): void;
}