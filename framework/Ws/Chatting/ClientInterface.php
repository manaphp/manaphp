<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Chatting;

interface ClientInterface
{
    public function pushToRoom(string $room, string|array $message): void;

    public function pushToId(string $room, int|array $id, string|array $message): void;

    public function pushToName(string $room, string|array $name, array $message): void;

    public function broadcast(string|array $message): void;

    public function closeRoom(string $room, string|array $message): void;

    public function kickoutId(string $room, string|array $id, string|array $message): void;

    public function kickoutName(string $room, string|array $name, array $message): void;
}