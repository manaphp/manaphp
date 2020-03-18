<?php
namespace ManaPHP\WebSocket\Server;

interface HandlerInterface
{
    /**
     * @param int $worker_id
     */
    public function onStart($worker_id);

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