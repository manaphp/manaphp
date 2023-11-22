<?php
declare(strict_types=1);

namespace ManaPHP\Swoole\Process\Event;

use Swoole\Http\Server;

class ProcessHandling
{
    public function __construct(public Server $server, public int $index)
    {

    }
}