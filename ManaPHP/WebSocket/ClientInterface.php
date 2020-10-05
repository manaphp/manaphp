<?php

namespace ManaPHP\WebSocket;

interface ClientInterface
{
    /**
     * @return string
     */
    public function getEndpoint();

    /**
     * @param string $message
     * @param float  $timeout
     *
     * @return \ManaPHP\WebSocket\Client\Message
     */
    public function request($message, $timeout = null);

    /**
     * @param callable $handler
     * @param int      $keepalive
     *
     * @return void
     */
    public function subscribe($handler, $keepalive = 60);
}