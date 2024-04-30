<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;
use function get_class;
use function is_object;

#[Verbosity(Verbosity::MEDIUM)]
class ServerPipeMessage implements JsonSerializable
{
    public function __construct(public Server $server, public int $src_worker_id, public mixed $message)
    {

    }

    public function jsonSerialize(): array
    {
        $type = is_object($this->message) ? get_class($this->message) : 'message';

        return [
            $type           => $this->message,
            'src_worker_id' => $this->src_worker_id,
        ];
    }
}