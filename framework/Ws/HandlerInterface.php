<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

interface HandlerInterface
{
    public function onOpen(int $fd): void;

    public function onClose(int $fd): void;

    public function onMessage(int $fd, string $data): void;
}