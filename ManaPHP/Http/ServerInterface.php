<?php

namespace ManaPHP\Http;

interface ServerInterface
{
    /**
     * @return void
     */
    public function start();

    public function send();
}