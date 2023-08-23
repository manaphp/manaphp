<?php
declare(strict_types=1);

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\ConfigInterface;
use ManaPHP\Debugging\DebuggerInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Http\Server\Event\ServerStart;
use Psr\Container\ContainerInterface;

class DebuggerBootstrapper implements BootstrapperInterface
{
    #[Inject] protected ListenerProviderInterface $listenerProvider;
    #[Inject] protected ConfigInterface $config;
    #[Inject] protected DebuggerInterface $debugger;

    #[Value] protected ?bool $enabled;

    public function bootstrap(ContainerInterface $container): void
    {
        if ($this->enabled ?? in_array($this->config->get('env'), ['dev', 'test'], true)) {
            $this->listenerProvider->add($this);
        }
    }

    public function onHttpServerStart(#[Event] ServerStart $event): void
    {
        $this->debugger->start();
    }
}