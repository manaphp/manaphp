<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestException;
use ManaPHP\Swoole\WorkersTrait;

class HttpExceptionsTotalCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected int $tasker_id = 0;

    protected array $totals = [];

    public function updateRequest(string $handler, string $exception): void
    {
        if (!isset($this->totals[$handler][$exception])) {
            $this->totals[$handler][$exception] = 0;
        } else {
            $this->totals[$handler][$exception]++;
        }
    }

    public function getResponse(): array
    {
        return $this->totals;
    }

    public function onRequestException(#[Event] RequestException $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            $this->task($this->tasker_id)->updateRequest($handler, \get_class($event->exception));
        }
    }

    public function export(): string
    {
        $totals = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->counter('app_http_exceptions_total', $totals, [], ['handler', 'exception']);
    }
}