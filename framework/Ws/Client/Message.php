<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Client;

use JsonSerializable;

class Message implements JsonSerializable
{
    public const TEXT_FRAME = 0x01;
    public const BINARY_FRAME = 0x02;
    public const CLOSE_FRAME = 0x08;
    public const PING_FRAME = 0x09;
    public const PONG_FRAME = 0x0A;

    public int $op_code;
    public string $payload;
    public float $elapsed;

    public function __construct(int $op_code, string $payload, float $elapsed)
    {
        $this->op_code = $op_code;
        $this->payload = $payload;
        $this->elapsed = $elapsed;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}