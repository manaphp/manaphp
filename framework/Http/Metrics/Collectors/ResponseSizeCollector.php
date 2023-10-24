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
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Swoole\WorkersInterface;

class ResponseSizeCollector implements CollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected WorkersInterface $workers;

    #[Autowired] protected array $buckets = [1 << 10, 1 << 12, 1 << 14, 1 << 16, 1 << 18, 1 << 20];

    protected array $histograms = [];

    public function taskUpdateMetric(string $path, int $size): void
    {
        if (($histogram = $this->histograms[$path] ?? null) === null) {
            $histogram = $this->histograms[$path] = new Histogram($this->buckets);
        }

        foreach ($this->buckets as $bucket) {
            if ($size <= $bucket) {
                $histogram->buckets[$bucket]++;
            }
        }

        $histogram->sum += $size;
        $histogram->count++;
    }

    public function taskExport(): array
    {
        return $this->histograms;
    }

    public function onRequestEnd(#[Event] RequestEnd $event)
    {
        $path = $this->dispatcher->getPath();
        $size = strlen($this->response->getContent());

        $arguments = [$path, $size];
        $this->workers->task([$this, 'taskUpdateMetric'], $arguments, 0);
    }

    public function export(): string
    {
        if (($histograms = $this->workers->taskwait([$this, 'taskExport'], [], 1, 0)) === false) {
            return '';
        }

        $result = '';
        foreach ($histograms as $path => $histogram) {
            $result .= $this->formatter->histogram(
                'app_http_response_size_bytes', $histogram,
                ['handler' => $path]
            );
        }

        return $result;
    }
}