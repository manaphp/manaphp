<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Ws\Pushing\Server\Event\ServerPushing;
use Psr\Log\LoggerInterface;

class WspClientTracer
{
    #[Inject] protected LoggerInterface $logger;

    public function onPush(#[Event] ServerPushing $event): void
    {
        $this->logger->debug($event, ['category' => 'wspClient.push']);
    }
}