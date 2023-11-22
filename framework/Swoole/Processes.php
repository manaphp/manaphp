<?php
declare(strict_types=1);

namespace ManaPHP\Swoole;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\EventDispatcherInterface;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Http\Server\Event\ServerReady;
use ManaPHP\Swoole\Process\Event\ProcessHandled;
use ManaPHP\Swoole\Process\Event\ProcessHandling;
use Psr\Container\ContainerInterface;
use Swoole\Http\Server;
use Swoole\Process;

class Processes implements ProcessesInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;

    #[Autowired] protected array $processes = [];

    #[Config] protected string $app_id;

    protected function getProcessTitle(ProcessInterface $process, int $index): string
    {
        \preg_match('#\w+$#', $process::class, $match);
        $name = \lcfirst($match[0]);
        return \sprintf('%s.%s.%d', $this->app_id, $name, $index);
    }

    protected function startProcess(Server $server, ProcessInterface $process)
    {
        $settings = $process->getSettings();

        $nums = $settings[ProcessInterface::SETTINGS_NUMS] ?? 1;
        for ($index = 0; $index < $nums; $index++) {
            $proc = new Process(function (Process $proc) use ($process, $index, $server) {
                \swoole_set_process_name($this->getProcessTitle($process, $index));

                $this->listenerProvider->add($process);

                $this->eventDispatcher->dispatch(new ProcessHandling($server, $proc, $index));
                $process->handle();
                $this->eventDispatcher->dispatch(new ProcessHandled($server, $proc, $index));
            }, false,
                $settings[ProcessInterface::SETTINGS_PIPE_TYPE] ?? SOCK_DGRAM,
                $settings[ProcessInterface::SETTINGS_ENABLE_COROUTINE] ?? true
            );

            $server->addProcess($proc);
        }
    }

    public function onServerReady(#[Event] ServerReady $event)
    {
        foreach ($this->processes as $definition) {
            /** @var ProcessInterface $process */
            $process = $this->container->get($definition);

            if ($process->isEnabled()) {
                $this->startProcess($event->server, $process);
            }
        }
    }

    public function bootstrap(): void
    {
        $this->listenerProvider->add($this);
    }
}