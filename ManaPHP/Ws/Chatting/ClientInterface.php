<?php

namespace ManaPHP\Ws\Chatting;

interface ClientInterface
{
    /**
     * @param string       $room
     * @param string|array $message
     *
     * @return void
     */
    public function pushToRoom($room, $message);

    /**
     * @param string       $room
     * @param string|array $id
     * @param string|array $message
     *
     * @return mixed
     */
    public function pushToId($room, $id, $message);

    /**
     * @param string       $room
     * @param string|array $name
     * @param string|array $message
     *
     * @return mixed
     */
    public function pushToName($room, $name, $message);

    /**
     * @param string|array $message
     *
     * @return void
     */
    public function broadcast($message);

    /**
     * @param string       $room
     * @param string|array $message
     *
     * @return void
     */
    public function closeRoom($room, $message);

    /**
     * @param string       $room
     * @param string|array $id
     * @param string|array $message
     *
     * @return void
     */
    public function kickoutId($room, $id, $message);

    /**
     * @param string       $room
     * @param string|array $name
     * @param string|array $message
     *
     * @return void
     */
    public function kickoutName($room, $name, $message);
}