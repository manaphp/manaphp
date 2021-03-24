<?php

namespace ManaPHP\Http\Client;

use ManaPHP\Event\EventArgs;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class Tracer extends \ManaPHP\Tracing\Tracer
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
        $request = $eventArgs->data['request'];

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
        $response = clone $eventArgs->data['response'];

        if (!$this->verbose) {
            unset($response->stats, $response->headers);
        }

        $this->logger->debug($response, 'httpClient.response');
    }
}