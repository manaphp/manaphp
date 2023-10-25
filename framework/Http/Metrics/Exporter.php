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

    #[Autowired] protected array $collectors = [];

    public function bootstrap(ContainerInterface $container): void
    {
        foreach ($this->collectors as $collector) {
            $this->listenerProvider->add($collector);
        }
    }

    public function export(): ResponseInterface
    {
        $metrics = '';
        foreach ($this->collectors as $name) {
            /** @var CollectorInterface $collector */
            $collector = $this->container->get($name);
            $m = $collector->export();
            $metrics .= $m;
        }

        return $this->response->setContent($metrics)->setContentType('text/plain; version=0.0.4; charset=utf-8');
    }
}