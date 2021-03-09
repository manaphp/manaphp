<?php

namespace ManaPHP\Http\Client;

interface EngineInterface
{
    /**
     * @param \ManaPHP\Http\Client\Request $request
     * @param string                       $body
     * @param bool                         $keepalive
     *
     * @return \ManaPHP\Http\Client\Response
     */
    public function request($request, $body, $keepalive = false);
}