<?php

namespace App\Listeners;

use ManaPHP\Event\Listener;

/**
 * @property-read \ManaPHP\ConfigInterface             $config
 * @property-read \ManaPHP\Debugging\DebuggerInterface $debugger
 */
class DebuggerListener extends Listener
{
    public function listen()
    {
        if ($this->config->get('debug')) {
            $this->debugger->start();
        }
    }
}