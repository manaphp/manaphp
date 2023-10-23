<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\AbstractCollector;
use ManaPHP\Http\Metrics\Collectors\ResponseSize\ExportRequestMessage;
use ManaPHP\Http\Metrics\Collectors\ResponseSize\ExportResponseMessage;
use ManaPHP\Http\Metrics\Collectors\ResponseSize\MetricUpdatedMessage;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Http\Server\Event\ServerTask;

class ResponseSizeCollector extends AbstractCollector
{
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;

    #[Autowired] protected array $buckets = [1 << 10, 1 << 12, 1 << 14, 1 << 16, 1 << 18, 1 << 20];

    protected array $histograms = [];

    public function onTask(#[Event] ServerTask $event)
    {
        $message = $event->data;

        if ($message instanceof MetricUpdatedMessage) {
            $path = $message->path;
            $size = $message->size;

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
        } elseif ($message instanceof ExportRequestMessage) {
            $this->server->finish(new ExportResponseMessage($this->histograms));
        }
    }

    public function onRequestEnd(#[Event] RequestEnd $event)
    {
        $path = $this->dispatcher->getPath();
        $size = strlen($this->response->getContent());
        $this->server->task(new MetricUpdatedMessage($path, $size), 0);
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
                'app_http_response_size_bytes', $histogram,
                ['handler' => $path]
            );
        }

        return $result;
    }
}