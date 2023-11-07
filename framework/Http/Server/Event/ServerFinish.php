<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;

#[Verbosity(Verbosity::HIGH)]
class ServerFinish implements JsonSerializable
{
    public function __construct(public Server $server, public int $task_id, public mixed $data)
    {

    }

    public function jsonSerialize(): array
    {
        $type = \is_object($this->data) ? \get_class($this->data) : 'data';

        return [
            $type     => $this->data,
            'task_id' => $this->task_id,
        ];
    }
}