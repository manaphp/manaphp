<?php

namespace ManaPHP\WebSocket\Client;

class Message implements \JsonSerializable
{
    const TEXT_FRAME = 0x01;
    const BINARY_FRAME = 0x02;
    const CLOSE_FRAME = 0x08;
    const PING_FRAME = 0x09;
    const PONG_FRAME = 0x0A;

    /**
     * @var int
     */
    public $op_code;

    /**
     * @var string
     */
    public $payload;

    /**
     * Message constructor.
     *
     * @param int    $op_code
     * @param string $payload
     */
    public function __construct($op_code, $payload)
    {
        $this->op_code = $op_code;
        $this->payload = $payload;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}