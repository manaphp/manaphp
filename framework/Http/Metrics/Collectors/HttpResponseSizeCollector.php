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
use ManaPHP\Swoole\WorkersTrait;

class HttpResponseSizeCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;

    #[Autowired] protected array $buckets = [1 << 10, 1 << 12, 1 << 14, 1 << 16, 1 << 18, 1 << 20];
    #[Autowired] protected int $tasker_id = 0;

    protected array $histograms = [];

    public function updateRequest(string $handler, int $size): void
    {
        if (($histogram = $this->histograms[$handler] ?? null) === null) {
            $histogram = $this->histograms[$handler] = new Histogram($this->buckets);
        }

        $histogram->update($size);
    }

    public function getResponse(): array
    {
        return $this->histograms;
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            $size = $this->response->getContentLength();

            $this->task($this->tasker_id)->updateRequest($handler, $size);
        }
    }

    public function export(): string
    {
        $histograms = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->histogram('app_http_response_size_bytes', $histograms, [], ['handler']);
    }
}