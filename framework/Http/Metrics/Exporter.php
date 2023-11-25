<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Http\ResponseInterface;
use Psr\Container\ContainerInterface;

class Exporter implements ExporterInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected ResponseInterface $response;

    #[Autowired] protected array $collectors
        = [
            'ManaPHP\Http\Metrics\Collectors\HttpRequestDurationCollector',
            'ManaPHP\Http\Metrics\Collectors\HttpRequestsTotalCollector',
            'ManaPHP\Http\Metrics\Collectors\ServerStatsCollector',
            'ManaPHP\Http\Metrics\Collectors\VersionCollector',
            'ManaPHP\Http\Metrics\Collectors\CoroutineStatsCollector',
            'ManaPHP\Http\Metrics\Collectors\CoroutineOptionsCollector',
            'ManaPHP\Http\Metrics\Collectors\HttpResponseSizeCollector',
            'ManaPHP\Http\Metrics\Collectors\MemoryUsageCollector',
            'ManaPHP\Http\Metrics\Collectors\RedisCommandCollector',
            'ManaPHP\Http\Metrics\Collectors\SqlStatementCollector',
            'ManaPHP\Http\Metrics\Collectors\RedisGetResponseSizeCollector',
            'ManaPHP\Http\Metrics\Collectors\RedisGetCommandDurationCollector',
        ];

    public function bootstrap(): void
    {
        foreach ($this->collectors as $collector) {
            $this->listenerProvider->add($collector);
        }
    }

    public function export(?array $collectors = null): ResponseInterface
    {
        $metrics = '';
        foreach ($collectors ?? $this->collectors as $name) {
            if ($name !== '' && $name !== null) {
                /** @var CollectorInterface $collector */
                $collector = $this->container->get($name);
                $m = $collector->export();
                $metrics .= $m;
            }
        }

        return $this->response->setContent($metrics)->setContentType('text/plain; version=0.0.4; charset=utf-8');
    }
}