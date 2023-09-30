<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Chatting;

interface ServerInterface
{
    public function start(): void;

    public function open(int $fd, string $room): void;

    public function close(int $fd, string $room): void;
}