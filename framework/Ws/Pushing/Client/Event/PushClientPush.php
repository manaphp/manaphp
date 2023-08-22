<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Pushing\Client\Event;


use ManaPHP\Ws\Pushing\ClientInterface;

class PushClientPush
{
    public function __construct(
        public ClientInterface $client,
        public string $type,
        public string $receivers,
        public string $message,
        public string $endpoint,
    ) {

    }
}