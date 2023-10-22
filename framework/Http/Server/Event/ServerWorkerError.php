<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;

#[Verbosity(Verbosity::LOW)]
class ServerWorkerError
{
    public function __construct(
        public Server $server,
        public int $worker_id,
        public int $worker_pid,
        public int $exit_code,
        public int $signal
    ) {

    }
}