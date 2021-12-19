<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Pushing;

interface ServerInterface
{
    public function start(): void;

    public function open(int $fd): void;

    public function close(int $fd): void;

    public function push(int $fd, string $message): void;

    public function pushToId(array $receivers, string $message): void;

    public function pushToName(array $receivers, string $message): void;

    public function pushToRoom(array $receivers, string $message): void;

    public function pushToRole(array $receivers, string $message): void;

    public function pushToAll(string $message): void;

    public function broadcast(string $message): void;
}