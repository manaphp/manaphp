<?php

namespace ManaPHP\Rpc\Http;

interface ServerInterface
{
    /**
     * @param \ManaPHP\Rpc\Http\Server\HandlerInterface $handler
     *
     * @return static
     */
    public function start($handler);

    /**
     * @return void
     */
    public function send();
}