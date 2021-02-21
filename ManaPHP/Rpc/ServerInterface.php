<?php

namespace ManaPHP\Rpc;

interface ServerInterface
{
    /**
     * @param \ManaPHP\Rpc\Server\HandlerInterface $handler
     *
     * @return static
     */
    public function start($handler);

    /**
     * @return void
     */
    public function send();
}