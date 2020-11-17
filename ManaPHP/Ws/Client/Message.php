<?php

namespace ManaPHP\Ws\Client;

use JsonSerializable;

class Message implements JsonSerializable
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
     * @var float
     */
    public $elapsed;

    /**
     * @param int    $op_code
     * @param string $payload
     * @param float  $elapsed
     */
    public function __construct($op_code, $payload, $elapsed)
    {
        $this->op_code = $op_code;
        $this->payload = $payload;
        $this->elapsed = $elapsed;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}