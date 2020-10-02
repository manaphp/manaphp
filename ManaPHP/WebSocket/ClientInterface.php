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
     * @return void
     */
    public function send($message, $timeout = null);

    /**
     * @param float $timeout
     *
     * @return \ManaPHP\WebSocket\Client\Message
     */
    public function recv($timeout = null);

    /**
     * @param callable $handler
     * @param int      $keepalive
     *
     * @return void
     */
    public function subscribe($handler, $keepalive = 60);

    /**
     * @param string $data
     *
     * @return static
     */
    public function ping($data = '');

    /**
     * @param string $data
     *
     * @return static
     */
    public function pong($data);

    /**
     * @return void
     */
    public function close();
}