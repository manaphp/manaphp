<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Metrics\WorkerCollectorInterface;
use ManaPHP\Http\ResponseInterface;

class HttpResponseSizeCollector implements WorkerCollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected ResponseInterface $response;

    #[Autowired] protected array $buckets = [1024, 11];

    protected array $histograms = [];

    public function updating(?string $handler): ?array
    {
        return $handler ? [$handler, $this->response->getContentLength()] : null;
    }

    public function updated(array $data): void
    {
        list($handler, $size) = $data;
        if (($histogram = $this->histograms[$handler] ?? null) === null) {
            $histogram = $this->histograms[$handler] = new Histogram($this->buckets);
        }

        $histogram->update($size);
    }

    public function querying(): array
    {
        return $this->histograms;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->histogram('app_http_response_size_bytes', $data, [], ['handler']);
    }
}