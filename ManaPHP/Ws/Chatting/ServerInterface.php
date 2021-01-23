<?php

namespace ManaPHP\Ws\Chatting;

interface ServerInterface
{
    /**
     * @return void
     */
    public function start();

    /**
     * @param int    $fd
     * @param string $room
     *
     * @return void
     */
    public function open($fd, $room = null);

    /**
     * @param int    $fd
     * @param string $room
     *
     * @return void
     */
    public function close($fd, $room = null);
}