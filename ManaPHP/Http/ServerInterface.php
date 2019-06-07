<?php
namespace ManaPHP\Http;

interface ServerInterface
{
    /**
     * @param \ManaPHP\Http\Server\RequestHandlerInterface $handler
     *
     * @return static
     */
    public function start($handler);

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function send($response);
}