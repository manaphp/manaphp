<?php

namespace ManaPHP\Rpc\Http\Server\Adapter;

class SwooleContext
{
    /**
     * @var int
     */
    public $fd;

    /**
     * @var \Swoole\Http\Response
     */
    public $response;
}