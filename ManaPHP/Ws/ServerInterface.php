<?php

namespace ManaPHP\Ws;

interface ServerInterface
{
    /**
     * @param \ManaPHP\Ws\Server\HandlerInterface $handler
     *
     * @return void
     */
    public function start($handler);

    /**
     * @param int   $fd
     * @param mixed $data
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

    /**
     * @param int $fd
     *
     * @return bool
     */
    public function exists($fd);

    /**
     * @return void
     */
    public function reload();
}