<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;

#[Verbosity(Verbosity::MEDIUM)]
class ServerPipeMessage implements JsonSerializable
{
    public function __construct(public Server $server, public int $src_worker_id, public mixed $message)
    {

    }

    public function jsonSerialize(): array
    {
        return [
            \is_object($this->message) ? \get_class($this->message) : 'message' => $this->message,
            'src_worker_id'                                                   => $this->src_worker_id,
        ];
    }
}