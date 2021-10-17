<?php

namespace ManaPHP\Rpc;

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