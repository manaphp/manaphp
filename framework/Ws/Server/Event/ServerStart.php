<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Ws\ServerInterface;
use Swoole\Http\Server;

#[Verbosity(Verbosity::LOW)]
class ServerStart
{
    public function __construct(
        public ServerInterface $server,
        public Server $swoole,
        public int $worker_id,
    ) {

    }
}