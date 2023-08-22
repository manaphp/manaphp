<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Http\ServerInterface;
use Swoole\Http\Server;

class ServerWorkerStart
{
    public function __construct(
        public ServerInterface $server,
        public Server $swoole,
        public int $worker_id,
    ) {

    }
}