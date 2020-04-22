<?php

namespace ManaPHP\Curl;

interface MultiInterface
{
    /**
     * @param string|array|\ManaPHP\Curl\Multi\Request $request
     * @param callable|array                           $callbacks
     *
     * @return static
     */
    public function add($request, $callbacks = null);

    /**
     * @param string|array $url
     * @param string       $target
     * @param callable     $callback
     *
     * @return static
     */
    public function download($url, $target, $callback = null);

    /**
     * @return static
     */
    public function start();

    /**
     * @param \ManaPHP\Curl\Multi\Response $response
     *
     * @return false|null
     */
    public function onSuccess($response);

    /**
     * @param \ManaPHP\Curl\Multi\Error $error
     *
     * @return false|null
     */
    public function onError($error);
}