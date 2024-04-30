<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\WorkerCollectorInterface;
use ManaPHP\Http\Server\Event\RequestException;
use function get_class;
use function in_array;

class HttpExceptionsTotalCollector implements WorkerCollectorInterface
{
    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;

    #[Autowired] protected array $ignored_exceptions = [];

    protected array $totals = [];

    public function getContext(int $cid = 0): HttpExceptionsTotalCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updating(?string $handler): ?array
    {
        $context = $this->getContext();

        return ($handler !== null && isset($context->exception)) ? [$handler, $context->exception] : null;
    }

    public function updated(array $data): void
    {
        list($handler, $exception) = $data;

        if (!isset($this->totals[$handler][$exception])) {
            $this->totals[$handler][$exception] = 0;
        } else {
            $this->totals[$handler][$exception]++;
        }
    }

    public function onRequestException(#[Event] RequestException $event): void
    {
        $exception = get_class($event->exception);

        if (!in_array($exception, $this->ignored_exceptions, true)) {
            $context = $this->getContext();
            $context->exception = $exception;
        }
    }

    public function querying(): array
    {
        return $this->totals;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->counter('app_http_exceptions_total', $data, [], ['handler', 'exception']);
    }
}