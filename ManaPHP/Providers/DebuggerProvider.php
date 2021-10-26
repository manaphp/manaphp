<?php

namespace ManaPHP\Providers;

use ManaPHP\Provider;

/**
 * @property-read \ManaPHP\Debugging\DebuggerInterface $debugger
 */
class DebuggerProvider extends Provider
{
    public function boot()
    {
        $this->debugger->start();
    }
}