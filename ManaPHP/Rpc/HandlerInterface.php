<?php

namespace ManaPHP\Rpc;

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