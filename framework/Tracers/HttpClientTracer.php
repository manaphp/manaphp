<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Client\HttpClientRequested;
use ManaPHP\Http\Client\HttpClientRequesting;
use ManaPHP\Tracer;

class HttpClientTracer extends Tracer
{
    public function onRequesting(#[Event] HttpClientRequesting $event): void
    {
        $request = $event->request;

        if ($request->method === 'POST' && $request->body) {
            $this->info($request->url, 'httpClient.request');
        }
    }

    public function onRequested(#[Event] HttpClientRequested $event): void
    {
        $response = clone $event->response;

        if (!$this->verbose) {
            unset($response->stats, $response->headers);
        }

        $this->debug($response, 'httpClient.response');
    }
}