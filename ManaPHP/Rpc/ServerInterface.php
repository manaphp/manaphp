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
     * @param \ManaPHP\Http\ResponseContext $response
     */
    public function send($response);
}