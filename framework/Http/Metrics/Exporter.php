<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Swoole\WorkersTrait;
use Psr\Container\ContainerInterface;

class Exporter implements ExporterInterface
{
    use WorkersTrait;

    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected array $collectors
        = [
            'ManaPHP\Http\Metrics\Collectors\ServerSettingsCollector',
            'ManaPHP\Http\Metrics\Collectors\ServerStatsCollector',
            'ManaPHP\Http\Metrics\Collectors\HttpRequestDurationCollector',
            'ManaPHP\Http\Metrics\Collectors\HttpRequestsTotalCollector',
            'ManaPHP\Http\Metrics\Collectors\VersionCollector',
            'ManaPHP\Http\Metrics\Collectors\CoroutineStatsCollector',
            'ManaPHP\Http\Metrics\Collectors\CoroutineOptionsCollector',
            'ManaPHP\Http\Metrics\Collectors\HttpResponseSizeCollector',
            'ManaPHP\Http\Metrics\Collectors\MemoryUsageCollector',
            'ManaPHP\Http\Metrics\Collectors\RedisCommandDurationCollector',
            'ManaPHP\Http\Metrics\Collectors\SqlStatementDurationCollector',
            'ManaPHP\Http\Metrics\Collectors\RedisGetResponseSizeCollector',
            'ManaPHP\Http\Metrics\Collectors\RedisGetCommandDurationCollector',
            'ManaPHP\Http\Metrics\Collectors\HttpExceptionsTotalCollector',
            'ManaPHP\Http\Metrics\Collectors\SqlTransactionDurationCollector',
            'ManaPHP\Http\Metrics\Collectors\PoolPopDurationCollector',
            'ManaPHP\Http\Metrics\Collectors\PoolsBusyTotalCollector',
        ];

    #[Autowired] protected int $tasker_id = 0;

    protected array $simple_collectors = [];

    public function bootstrap(): void
    {
        foreach ($this->collectors as $name) {
            $collector = $this->container->get($name);
            if ($collector instanceof SimpleCollectorInterface) {
                $this->simple_collectors[] = $name;
            }
        }

        $this->listenerProvider->add($this);

        foreach ($this->collectors as $collector) {
            $this->listenerProvider->add($collector);
        }
    }

    public function updated(array $data): void
    {
        foreach ($data as $name => $args) {
            /** @var SimpleCollectorInterface $collector */
            $collector = $this->container->get($name);
            $collector->updated($args);
        }
    }

    public function querying(): array
    {
        $data = [];
        foreach ($this->simple_collectors as $name) {
            /** @var SimpleCollectorInterface $collector */
            $collector = $this->container->get($name);
            $data[$name] = $collector->querying();
        }

        return $data;
    }

    public function onRequestEnd(#[Event] RequestEnd $event)
    {
        $handler = $this->dispatcher->getHandler();

        $data = [];
        foreach ($this->collectors as $name) {
            $collector = $this->container->get($name);
            if ($collector instanceof SimpleCollectorInterface) {
                if (($r = $collector->updating($handler)) !== null) {
                    $data[$name] = $r;
                }
            }
        }

        $this->task($this->tasker_id)->updated($data);
    }

    public function export(?array $collectors = null): ResponseInterface
    {
        $simple_collectors = $this->task($this->tasker_id, 1.0)->querying();

        $metrics = '';
        foreach ($collectors ?? $this->collectors as $name) {
            if ($name !== '' && $name !== null) {
                /** @var CollectorInterface $collector */
                $collector = $this->container->get($name);

                if (($data = $simple_collectors[$name] ?? null) === null) {
                    $m = $collector->export(null);
                } else {
                    $m = $collector->export($data);
                }

                $metrics .= $m;
            }
        }

        return $this->response->setContent($metrics)->setContentType('text/plain; version=0.0.4; charset=utf-8');
    }
}