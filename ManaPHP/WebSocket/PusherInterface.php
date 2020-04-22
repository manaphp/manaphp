<?php

namespace ManaPHP\WebSocket;

interface PusherInterface
{
    /**
     * @param int|int[]    $receivers
     * @param string|array $message
     * @param string       $endpoint
     *
     * @return void
     */
    public function pushToId($receivers, $message, $endpoint = null);

    /**
     * @param string|string[] $receivers
     * @param string|array    $message
     * @param string|array    $endpoint
     *
     * @return void
     */
    public function pushToName($receivers, $message, $endpoint = null);

    /**
     * @param string|string[] $receivers
     * @param string|array    $message
     * @param string          $endpoint
     *
     * @return void
     */
    public function pushToRole($receivers, $message, $endpoint = null);

    /**
     * @param string|array $message
     * @param string       $endpoint
     *
     * @return void
     */
    public function pushToAll($message, $endpoint = null);

    /**
     * @param string|array $message
     * @param string       $endpoint
     *
     * @return void
     */
    public function broadcast($message, $endpoint = null);
}