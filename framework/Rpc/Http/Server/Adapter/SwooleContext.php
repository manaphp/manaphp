<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Http\Server\Adapter;

use Swoole\Http\Response;

class SwooleContext
{
    public int $fd;
    public Response $response;
}