<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Client\HttpClientRequested;
use ManaPHP\Http\Client\HttpClientRequesting;
use Psr\Log\LoggerInterface;

class HttpClientTracer
{
    #[Inject] protected LoggerInterface $logger;

    #[Value] protected bool $verbose = true;

    public function onRequesting(#[Event] HttpClientRequesting $event): void
    {
        $request = $event->request;

        if ($request->method === 'POST' && $request->body) {
            $this->logger->info($request->url, ['category' => 'httpClient.request']);
        }
    }

    public function onRequested(#[Event] HttpClientRequested $event): void
    {
        $response = clone $event->response;

        if (!$this->verbose) {
            unset($response->stats, $response->headers);
        }

        $this->logger->debug($response, ['category' => 'httpClient.response']);
    }
}