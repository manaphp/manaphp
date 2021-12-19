<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

interface ServerInterface
{
    public function start(): void;

    public function push(int $fd, mixed $data): bool;

    public function broadcast(string $data): void;

    public function disconnect(int $fd): bool;

    public function exists(int $fd): bool;

    public function reload(): void;

    public function getWorkerId(): int;
}