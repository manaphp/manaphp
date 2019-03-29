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
     * @param int $code
     *
     * @return static
     */
    public function setStatus($code);

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

    /**
     * @param string $file
     *
     * @return static
     */
    public function sendFile($file);

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function send($response);
}