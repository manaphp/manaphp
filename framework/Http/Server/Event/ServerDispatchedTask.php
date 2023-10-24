<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;

#[Verbosity(Verbosity::MEDIUM)]
class ServerDispatchedTask implements JsonSerializable
{
    public function __construct(
        public Server $server,
        public int $task_id,
        public int $src_worker_id,
        public mixed $data
    ) {

    }

    public function jsonSerialize(): array
    {
        return [
            is_object($this->data) ? get_class($this->data) : 'data' => $this->data,
            'task_id'                                                => $this->task_id,
            'src_worker_id'                                          => $this->src_worker_id,
        ];
    }
}