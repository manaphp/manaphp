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

class RequestDurationCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected RequestInterface $request;

    #[Autowired] protected array $buckets = ['0.1', '0.2', '0.4', '1', '3', '10'];

    protected array $histograms = [];

    public function taskUpdateMetrics(string $path, float $elapsed): void
    {
        if (($histogram = $this->histograms[$path] ?? null) === null) {
            $histogram = $this->histograms[$path] = new Histogram($this->buckets);
        }

        foreach ($this->buckets as $le) {
            if ($elapsed <= $le) {
                $histogram->buckets[$le]++;
            }
        }

        $histogram->sum += $elapsed;
        $histogram->count++;
    }

    public function taskExport(): array
    {
        return $this->histograms;
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            $elapsed = $this->request->elapsed();

            $this->task(0)->taskUpdateMetrics($handler, $elapsed);
        }
    }

    public function export(): string
    {
        $histograms = $this->taskwait(1.0, 0)->taskExport();

        return $this->formatter->histogram('app_http_request_duration_seconds', $histograms, [], ['handler']);
    }
}