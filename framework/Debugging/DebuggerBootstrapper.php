<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Http\Server\Event\ServerStart;
use Psr\Container\ContainerInterface;

class DebuggerBootstrapper implements BootstrapperInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected DebuggerInterface $debugger;

    #[Autowired] protected ?bool $enabled;

    #[Config] protected string $app_env;

    public function bootstrap(ContainerInterface $container): void
    {
        if ($this->enabled ?? in_array($this->app_env, ['dev', 'test'], true)) {
            $this->listenerProvider->add($this);
        }
    }

    public function onHttpServerStart(#[Event] ServerStart $event): void
    {
        $this->debugger->start();
    }
}