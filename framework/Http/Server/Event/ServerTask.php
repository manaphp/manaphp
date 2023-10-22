<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;

#[Verbosity(Verbosity::MEDIUM)]
class ServerTask
{
    public function __construct(
        public Server $server,
        public int $task_id,
        public int $src_worker_id,
        public mixed $data
    ) {

    }
}