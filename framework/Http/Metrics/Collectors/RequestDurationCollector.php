<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\AbstractCollector;
use ManaPHP\Http\Metrics\Collectors\RequestDuration\ExportRequestMessage;
use ManaPHP\Http\Metrics\Collectors\RequestDuration\ExportResponseMessage;
use ManaPHP\Http\Metrics\Collectors\RequestDuration\MetricRequestMessage;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Http\Server\Event\ServerTask;

class RequestDurationCollector extends AbstractCollector
{
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected RequestInterface $request;

    #[Autowired] protected array $buckets = ['0.1', '0.2', '0.4', '1', '3', '10'];

    protected array $histograms = [];

    public function onTask(#[Event] ServerTask $event)
    {
        $message = $event->data;

        if ($message instanceof MetricRequestMessage) {
            $path = $message->path;
            $elapsed = $message->elapsed;

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
        } elseif ($message instanceof ExportRequestMessage) {
            $this->server->finish(new ExportResponseMessage($this->histograms));
        }
    }

    public function onRequestEnd(#[Event] RequestEnd $event)
    {
        $path = $this->dispatcher->getPath();
        $elapsed = $this->request->getElapsedTime();
        $this->server->task(new MetricRequestMessage($path, $elapsed), 0);
    }

    public function export(): string
    {
        /** @var ExportResponseMessage $metrics */
        if (($metrics = $this->server->taskwait(new ExportRequestMessage(), 1, 0)) === false) {
            return '';
        }

        $result = '';
        foreach ($metrics->histograms as $path => $histogram) {
            $result .= $this->formatter->histogram(
                'app_http_request_duration_seconds', $histogram,
                ['handler' => $path]
            );
        }

        return $result;
    }
}