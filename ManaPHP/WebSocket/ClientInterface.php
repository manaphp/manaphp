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
     * @return \ManaPHP\WebSocket\Client\Message|false
     */
    public function recv($timeout = 0.0);

    /**
     * @return void
     */
    public function close();
}