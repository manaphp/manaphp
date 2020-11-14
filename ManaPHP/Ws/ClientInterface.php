<?php

namespace ManaPHP\Ws;

interface ClientInterface
{
    /**
     * @return string
     */
    public function getEndpoint();

    /**
     * @param string $endpoint
     *
     * @return static
     */
    public function setEndpoint($endpoint);

    /**
     * @param string $message
     * @param float  $timeout
     *
     * @return \ManaPHP\Ws\Client\Message
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