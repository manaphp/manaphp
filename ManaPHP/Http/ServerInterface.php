<?php

namespace ManaPHP\Http;

interface ServerInterface
{
    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
     *
     * @return void
     */
    public function start($handler);

    /**
     * @param \ManaPHP\Http\ResponseContext $response
     */
    public function send($response);
}