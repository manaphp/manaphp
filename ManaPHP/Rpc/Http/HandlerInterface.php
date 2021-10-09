<?php

namespace ManaPHP\Rpc\Http;

interface HandlerInterface
{
    /**
     * @return bool
     */
    public function authenticate();

    /**
     * @return void
     */
    public function handle();
}