<?php

namespace ManaPHP\Http\Client;

use ManaPHP\Event\EventArgs;

class Tracer extends \ManaPHP\Event\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

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
        $request = $eventArgs->data;

        if ($request->method === 'POST' && $request->body) {
            $this->logger->info($eventArgs->data, 'httpClient.request');
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
        $response = clone $eventArgs->data;

        if (!$this->_verbose) {
            unset($response->stats, $response->headers);
        }

        $this->logger->debug($response, 'httpClient.response');
    }
}