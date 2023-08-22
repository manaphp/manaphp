<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Http\ServerInterface;
use Swoole\Http\Server;

class ServerManagerStart
{
    public function __construct(
        public ServerInterface $server,
        public Server $swoole,
    ) {

    }
}