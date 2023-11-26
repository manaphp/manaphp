<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Swoole\WorkersTrait;

class HttpRequestDurationCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected RequestInterface $request;

    #[Autowired] protected array $buckets = [0.008, 11];
    #[Autowired] protected int $tasker_id = 0;

    protected array $histograms = [];

    public function updateRequest(string $handler, float $elapsed): void
    {
        if (($histogram = $this->histograms[$handler] ?? null) === null) {
            $histogram = $this->histograms[$handler] = new Histogram($this->buckets);
        }
        $histogram->update($elapsed);
    }

    public function getResponse(): array
    {
        return $this->histograms;
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            $elapsed = $this->request->elapsed();

            $this->task($this->tasker_id)->updateRequest($handler, $elapsed);
        }
    }

    public function export(): string
    {
        $histograms = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->histogram('app_http_request_duration_seconds', $histograms, [], ['handler']);
    }
}