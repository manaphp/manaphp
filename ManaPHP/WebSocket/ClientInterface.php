<?php
namespace ManaPHP\WebSocket;

interface ClientInterface
{
    /**
     * @return string
     */
    public function getEndpoint();

    /**
     * @return bool
     */
    public function hasMessage();

    /**
     * @param string $message
     *
     * @return void
     */
    public function sendMessage($message);

    /**
     * @param float
     *
     * @return string|false
     */
    public function recvMessage($timeout = 0.0);

    /**
     * @return void
     */
    public function close();
}