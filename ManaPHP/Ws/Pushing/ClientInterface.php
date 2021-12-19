<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Pushing;

interface ClientInterface
{
    public function pushToId(int|array $receivers, string|array $message, ?string $endpoint = null): void;

    public function pushToName(string|array $receivers, string|array $message, ?string $endpoint = null): void;

    public function pushToRoom(string|array $receivers, string|array $message, ?string $endpoint = null): void;

    public function pushToRole(string|array $receivers, string|array $message, ?string $endpoint = null): void;

    public function pushToAll(string|array $message, ?string $endpoint = null): void;

    public function broadcast(string|array $message, ?string $endpoint = null): void;
}