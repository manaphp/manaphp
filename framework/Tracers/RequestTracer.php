<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Server\Event\RequestBegin;
use Psr\Log\LoggerInterface;

class RequestTracer
{
    #[Inject] protected LoggerInterface $logger;
    #[Inject] protected RequestInterface $request;

    public function onBegin(#[Event] RequestBegin $event): void
    {
        $this->logger->debug('{0}', [json_stringify($this->request->all()), 'category' => 'http.request']);
    }
}