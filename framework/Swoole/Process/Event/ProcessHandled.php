<?php
declare(strict_types=1);

namespace ManaPHP\Swoole\Process\Event;

use Swoole\Http\Server;
use Swoole\Process;

class ProcessHandled
{
    public function __construct(public Server $server, public Process $process, public int $index)
    {

    }
}