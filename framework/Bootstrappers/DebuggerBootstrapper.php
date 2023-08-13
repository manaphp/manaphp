<?php
declare(strict_types=1);

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Component;
use ManaPHP\ConfigInterface;
use ManaPHP\Debugging\DebuggerInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Event\EventTrait;
use Psr\Container\ContainerInterface;

class DebuggerBootstrapper extends Component implements BootstrapperInterface
{
    use EventTrait;

    #[Inject] protected ConfigInterface $config;
    #[Inject] protected DebuggerInterface $debugger;

    protected bool $enabled;

    public function __construct(?bool $enabled = null)
    {
        $this->enabled = $enabled ?? in_array($this->config->get('env'), ['dev', 'test'], true);
    }

    public function bootstrap(ContainerInterface $container): void
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