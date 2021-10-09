<?php

namespace ManaPHP\Ws;

interface HandlerInterface
{
    /**
     * @param int $fd
     */
    public function onOpen($fd);

    /**
     * @param int $fd
     */
    public function onClose($fd);

    /**
     * @param int    $fd
     * @param string $data
     */
    public function onMessage($fd, $data);
}