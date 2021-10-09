<?php

namespace ManaPHP\Socket;

interface HandlerInterface
{
    /**
     * @param int $fd
     *
     * @return mixed
     */
    public function onConnect($fd);

    /**
     * @param int    $fd
     * @param string $data
     *
     * @return mixed
     */
    public function onReceive($fd, $data);

    /**
     * @param int $fd
     *
     * @return void
     */
    public function onClose($fd);
}
