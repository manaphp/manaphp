<?php

namespace ManaPHP\Ws\Client;

use ManaPHP\Event\EventArgs;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class Tracer extends \ManaPHP\Event\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->attachEvent('wsClient:send', [$this, 'onSend']);
        $this->attachEvent('wsClient:recv', [$this, 'onRecv']);
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onSend(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'wsClient.send');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onRecv(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'wsClient.recv');
    }
}