<?php

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class HttpClientTracer extends Tracer
{
    public function listen()
    {
        $this->attachEvent('httpClient:requesting', [$this, 'onRequesting']);
        $this->attachEvent('httpClient:requested', [$this, 'onRequested']);
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onRequesting(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\Client\Request $request */
        $request = $eventArgs->data['request'];

        if ($request->method === 'POST' && $request->body) {
            $this->info($eventArgs->data, 'httpClient.request');
        }
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onRequested(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\Client\Response $response */
        $response = clone $eventArgs->data['response'];

        if (!$this->verbose) {
            unset($response->stats, $response->headers);
        }

        $this->debug($response, 'httpClient.response');
    }
}