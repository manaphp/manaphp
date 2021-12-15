<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use Swoole\Http\Response;

class SwooleContext
{
    public Response $response;
}
