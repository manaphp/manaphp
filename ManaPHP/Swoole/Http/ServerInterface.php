<?php
namespace ManaPHP\Swoole\Http;

interface ServerInterface
{
    /**
     * @param callable $handler
     *
     * @return static
     */
    public function start($handler);

    /**
     * @param array $headers
     *
     * @return static
     */
    public function sendHeaders($headers);

    /**
     * @param array $cookies
     *
     * @return static
     */
    public function sendCookies($cookies);

    /**
     * @param string $content
     *
     * @return static
     */
    public function sendContent($content);
}