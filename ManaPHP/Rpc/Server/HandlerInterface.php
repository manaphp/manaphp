<?php

namespace ManaPHP\Rpc\Server;

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