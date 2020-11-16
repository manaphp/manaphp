<?php

namespace ManaPHP\Http;

interface CurlMultiInterface
{
    /**
     * @param string|array|\ManaPHP\Http\CurlMulti\Request $request
     * @param callable|array                               $callbacks
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
     * @param \ManaPHP\Http\CurlMulti\Response $response
     *
     * @return false|null
     */
    public function onSuccess($response);

    /**
     * @param \ManaPHP\Http\CurlMulti\Error $error
     *
     * @return false|null
     */
    public function onError($error);
}