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
     * @param array $cookies
     *
     * @return static
     */
    public function sendCookies($cookies);

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function send($response);
}