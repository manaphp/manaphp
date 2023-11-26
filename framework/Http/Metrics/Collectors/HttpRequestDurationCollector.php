<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Metrics\SimpleCollectorInterface;
use ManaPHP\Http\RequestInterface;

class HttpRequestDurationCollector implements SimpleCollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected RequestInterface $request;

    #[Autowired] protected array $buckets = [0.008, 11];

    protected array $histograms = [];

    public function updating(?string $handler): ?array
    {
        return $handler ? [$handler, $this->request->elapsed()] : null;
    }

    public function updated(array $data): void
    {
        list ($handler, $elapsed) = $data;

        if (($histogram = $this->histograms[$handler] ?? null) === null) {
            $histogram = $this->histograms[$handler] = new Histogram($this->buckets);
        }
        $histogram->update($elapsed);
    }

    public function querying(): array
    {
        return $this->histograms;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->histogram('app_http_request_duration_seconds', $data, [], ['handler']);
    }
}