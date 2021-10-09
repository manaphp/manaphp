<?php

namespace ManaPHP\Socket;

interface ServerInterface
{
    /**
     * @return mixed
     */
    public function start();

    /**
     * @param int $fd
     *
     * @return array
     */
    public function getClientInfo($fd);

    /**
     * @param int    $fd
     * @param string $data
     *
     * @return mixed
     */
    public function send($fd, $data);

    /**
     * @param int    $fd
     * @param string $filename
     * @param int    $offset
     * @param int    $length
     *
     * @return mixed
     */
    public function sendFile($fd, $filename, $offset = 0, $length = 0);

    /**
     * @param int  $fd
     * @param bool $reset
     *
     * @return bool
     */
    public function close($fd, $reset = false);
}