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
     *
     * @return void
     */
    public function send($message);

    /**
     * @param float
     *
     * @return \ManaPHP\WebSocket\Client\Message|null
     */
    public function recv($timeout = 0.0);

    /**
     * @param callable $handler
     *
     * @return static
     */
    public function subscribe($handler);

    /**
     * @return void
     */
    public function close();
}