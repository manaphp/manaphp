<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Pushing\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Ws\Pushing\ServerInterface;

#[Verbosity(Verbosity::HIGH)]
class ServerPushed extends AbstractEvent
{
    public function __construct(
        public ServerInterface $server,
        public string $type,
        public array $receivers,
        public string $message,
    ) {

    }
}