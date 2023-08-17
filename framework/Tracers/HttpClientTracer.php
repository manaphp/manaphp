<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Eventing\EventArgs;
use ManaPHP\Tracer;

class HttpClientTracer extends Tracer
{
    public function listen(): void
    {
        $this->attachEvent('httpClient:requesting', [$this, 'onRequesting']);
        $this->attachEvent('httpClient:requested', [$this, 'onRequested']);
    }

    public function onRequesting(EventArgs $eventArgs): void
    {
        /** @var \ManaPHP\Http\Client\Request $request */
        $request = $eventArgs->data['request'];

        if ($request->method === 'POST' && $request->body) {
            $this->info($eventArgs->data, 'httpClient.request');
        }
    }

    public function onRequested(EventArgs $eventArgs): void
    {
        /** @var \ManaPHP\Http\Client\Response $response */
        $response = clone $eventArgs->data['response'];

        if (!$this->verbose) {
            unset($response->stats, $response->headers);
        }

        $this->debug($response, 'httpClient.response');
    }
}