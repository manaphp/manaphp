<?php
namespace ManaPHP\WebSocket;

interface ServerInterface
{
    /**
     * @return mixed
     */
    public function start();

    /**
     * @param int    $fd
     * @param string $data
     *
     * @return bool
     */
    public function push($fd, $data);

    /**
     * @param string $data
     *
     * @return int
     */
    public function broadcast($data);

    /**
     * @param int $fd
     *
     * @return bool
     */
    public function disconnect($fd);
}