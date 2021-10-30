<?php

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Debugging\DebuggerInterface $debugger
 */
class DebuggerBootstrapper extends Component implements BootstrapperInterface
{
    public function bootstrap()
    {
        $this->debugger->start();
    }
}