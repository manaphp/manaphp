<?php

namespace ManaPHP\Ws\Client;

use ManaPHP\Event\EventArgs;

class Tracer extends \ManaPHP\Tracing\Tracer
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
        $this->debug($eventArgs->data, 'wsClient.send');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onRecv(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'wsClient.recv');
    }
}