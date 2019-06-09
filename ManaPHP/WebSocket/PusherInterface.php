<?php
namespace ManaPHP\WebSocket;

interface PusherInterface
{
    /**
     * @param int|int[]                $fd
     * @param string|\JsonSerializable $message
     * @param string                   $endpoint
     *
     * @return void
     */
    public function pushToFd($fd, $message, $endpoint = null);

    /**
     * @param string|string[]          $user
     * @param string|\JsonSerializable $message
     * @param string                   $endpoint
     *
     * @return void
     */
    public function pushToUser($user, $message, $endpoint = null);

    /**
     * @param string|\JsonSerializable $message
     * @param string                   $endpoint
     *
     * @return void
     */
    public function pushToAll($message, $endpoint = null);

    /**
     * @param string                   $room
     * @param string|\JsonSerializable $message
     * @param array                    $excluded
     * @param string                   $endpoint
     *
     * @return void
     */
    public function pushToRoom($room, $message, $excluded = [], $endpoint = null);
}