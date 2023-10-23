<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\AbstractCollector;
use ManaPHP\Http\Metrics\Collectors\RequestsTotal\ExportRequestMessage;
use ManaPHP\Http\Metrics\Collectors\RequestsTotal\ExportResponseMessage;
use ManaPHP\Http\Metrics\Collectors\RequestsTotal\MetricUpdatedMessage;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Http\Server\Event\ServerTask;

class RequestsTotalCollector extends AbstractCollector
{
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;

    protected array $totals;

    public function onTask(#[Event] ServerTask $event)
    {
        $message = $event->data;

        if ($message instanceof MetricUpdatedMessage) {
            $code = $message->code;
            $handler = $message->handler;
            if (!isset($this->totals[$code][$handler])) {
                $this->totals[$code][$handler] = 0;
            } else {
                $this->totals[$code][$handler]++;
            }
        } elseif ($message instanceof ExportRequestMessage) {
            $this->server->finish(new ExportResponseMessage($this->totals));
        }
    }

    public function onRequestEnd(#[Event] RequestEnd $event)
    {
        $code = $this->response->getStatusCode();
        $handler = $this->dispatcher->getPath();
        $this->server->task(new MetricUpdatedMessage($code, $handler), 0);
    }

    public function export(): string
    {
        /** @var ExportResponseMessage $metrics */
        if (($metrics = $this->server->taskwait(new ExportRequestMessage(), 1, 0)) === false) {
            return '';
        }

        return $this->formatter->counter(
            'app_http_requests_total', $metrics->totals,
            [], ['code', 'handlers']
        );
    }
}