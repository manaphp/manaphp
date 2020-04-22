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
     * @param int      $keepalive
     *
     * @return void
     */
    public function subscribe($handler, $keepalive = 0);

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