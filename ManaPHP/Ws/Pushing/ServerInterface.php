<?php

namespace ManaPHP\Ws\Pushing;

interface ServerInterface
{
    /**
     * @return void
     */
    public function start();

    /**
     * @param int $fd
     *
     * @return void
     */
    public function open($fd);

    /**
     * @param int $fd
     *
     * @return void
     */
    public function close($fd);

    /**
     * @param int    $fd
     * @param string $message
     *
     * @return void
     */
    public function push($fd, $message);

    /**
     * @param array  $receivers
     * @param string $message
     *
     * @return void
     */
    public function pushToId($receivers, $message);

    /**
     * @param array  $receivers
     * @param string $message
     */
    public function pushToName($receivers, $message);

    /**
     * @param array  $receivers
     * @param string $message
     */
    public function pushToRole($receivers, $message);

    public function pushToAll($message);

    public function broadcast($message);
}