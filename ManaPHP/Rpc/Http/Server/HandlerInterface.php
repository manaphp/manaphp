<?php

namespace ManaPHP\Rpc\Http\Server;

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