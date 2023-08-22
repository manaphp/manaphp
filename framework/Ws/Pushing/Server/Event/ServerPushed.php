<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Pushing\Server\Event;

use ManaPHP\Ws\Pushing\ServerInterface;

class ServerPushed
{
    public function __construct(
        public ServerInterface $server,
        public string $type,
        public array $receivers,
        public string $message,
    ) {

    }
}