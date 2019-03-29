<?php
namespace ManaPHP\Swoole\Http;

interface ServerInterface
{
    /**
     * @param callable|array $handler
     *
     * @return static
     */
    public function start($handler);

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function send($response);
}