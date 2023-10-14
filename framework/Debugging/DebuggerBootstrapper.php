<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

use ManaPHP\BootstrapperInterface;
use ManaPHP\ConfigInterface;
use ManaPHP\Debugging\DebuggerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Http\Server\Event\ServerStart;
use Psr\Container\ContainerInterface;

class DebuggerBootstrapper implements BootstrapperInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected DebuggerInterface $debugger;

    #[Autowired] protected ?bool $enabled;

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