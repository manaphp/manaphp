<?php
namespace ManaPHP\WebSocket;

interface ClientInterface
{
    /**
     * @return bool
     */
    public function hasMessage();

    /**
     * @param string $data
     *
     * @return void
     */
    public function sendMessage($data);

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