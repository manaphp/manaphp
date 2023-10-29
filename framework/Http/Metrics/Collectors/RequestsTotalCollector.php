<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Swoole\WorkersInterface;

class RequestsTotalCollector implements CollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected WorkersInterface $workers;

    protected array $totals = [];

    public function taskUpdateMetrics(int $code, string $handler): void
    {
        if (!isset($this->totals[$code][$handler])) {
            $this->totals[$code][$handler] = 0;
        } else {
            $this->totals[$code][$handler]++;
        }
    }

    public function taskExport(): array
    {
        return $this->totals;
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            $code = $this->response->getStatusCode();

            $arguments = [$code, $handler];
            $this->workers->task([$this, 'taskUpdateMetrics'], $arguments, 0);
        }
    }

    public function export(): string
    {
        if (($totals = $this->workers->taskwait([$this, 'taskExport'], [], 1, 0)) === false) {
            return '';
        }

        return $this->formatter->counter('app_http_requests_total', $totals, [], ['code', 'handler']);
    }
}