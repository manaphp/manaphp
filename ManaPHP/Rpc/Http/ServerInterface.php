<?php

namespace ManaPHP\Rpc\Http;

interface ServerInterface
{
    /**
     * @return static
     */
    public function start();

    /**
     * @return void
     */
    public function send();
}