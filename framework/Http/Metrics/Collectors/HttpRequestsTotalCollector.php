<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\SimpleCollectorInterface;
use ManaPHP\Http\ResponseInterface;

class HttpRequestsTotalCollector implements SimpleCollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected ResponseInterface $response;

    protected array $totals = [];

    public function updating(?string $handler): ?array
    {
        return $handler ? [$handler, $this->response->getStatusCode()] : null;
    }

    public function updated(array $data): void
    {
        list($handler, $code) = $data;

        if (!isset($this->totals[$code][$handler])) {
            $this->totals[$code][$handler] = 1;
        } else {
            $this->totals[$code][$handler]++;
        }
    }

    public function querying(): array
    {
        return $this->totals;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->counter('app_http_requests_total', $data, [], ['code', 'handler']);
    }
}