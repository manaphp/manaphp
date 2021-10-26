<?php

namespace App\Listeners;

use ManaPHP\Event\Listener;

/**
 * @property-read \ManaPHP\Http\Middleware\ManagerInterface $middlewareManager
 */
class MiddlewareListener extends Listener
{
    public function listen()
    {
        $this->middlewareManager->listen();
    }
}