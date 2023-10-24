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
use ManaPHP\Swoole\WorkersInterface;

class RequestDurationCollector implements CollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected WorkersInterface $workers;

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

    public function onRequestEnd(#[Event] RequestEnd $event)
    {
        $path = $this->dispatcher->getPath();
        $elapsed = $this->request->getElapsedTime();

        $arguments = [$path, $elapsed];
        $this->workers->task([$this, 'taskUpdateMetrics'], $arguments, 0);
    }

    public function export(): string
    {
        if (($histograms = $this->workers->taskwait([$this, 'taskExport'], [], 1, 0)) === false) {
            return '';
        }

        $result = '';
        foreach ($histograms as $path => $histogram) {
            $result .= $this->formatter->histogram('app_http_request_duration_seconds', $histogram, ['handler' => $path]
            );
        }

        return $result;
    }
}