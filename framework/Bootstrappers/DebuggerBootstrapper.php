<?php
declare(strict_types=1);

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Component;

/**
 * @property-read \ManaPHP\ConfigInterface             $config
 * @property-read \ManaPHP\Debugging\DebuggerInterface $debugger
 */
class DebuggerBootstrapper extends Component implements BootstrapperInterface
{
    protected bool $enabled;

    public function __construct(?bool $enabled = null)
    {
        $this->enabled = $enabled ?? in_array($this->config->get('env'), ['dev', 'test']);
    }

    public function bootstrap(): void
    {
        if ($this->enabled) {
            $this->attachEvent('httpServer:start', [$this, 'onHttpServerStart']);
        }
    }

    public function onHttpServerStart(): void
    {
        $this->debugger->start();
    }
}