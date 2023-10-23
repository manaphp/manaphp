<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\AbstractCollector;
use ManaPHP\Http\Metrics\Collectors\CoroutineStats\ExportRequestMessage;
use ManaPHP\Http\Metrics\Collectors\CoroutineStats\ExportResponseMessage;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Server\Event\ServerPipeMessage;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class CoroutineStatsCollector extends AbstractCollector
{
    use ContextTrait;

    public function onServerPipeMessage(#[Event] ServerPipeMessage $event)
    {
        $message = $event->message;
        if ($message instanceof ExportRequestMessage) {
            $this->server->sendMessage(
                new ExportResponseMessage($message->cid, Coroutine::stats()), $event->src_worker_id
            );
        } elseif ($message instanceof ExportResponseMessage) {
            $context = $this->getContext($message->cid);
            $context->stats[$event->src_worker_id] = $message->stats;
            $context->channel->push(1);
        }
    }

    public function export(): string
    {
        /** @var CoroutineStatsCollectorContext $context */
        $context = $this->getContext();
        $worker_num = $this->server->setting['worker_num'];
        $context->channel = new Channel($worker_num);
        $context->stats[$this->server->worker_id] = Coroutine::stats();
        $context->channel->push(1);

        $request = new ExportRequestMessage(Coroutine::getCid());
        for ($worker_id = 0; $worker_id < $worker_num; $worker_id++) {
            if ($this->server->worker_id !== $worker_id) {
                $this->server->sendMessage($request, $worker_id);
            }
        }

        $end_time = microtime(true) + 0.3;
        do {
            $timeout = $end_time - microtime(true);
            if ($timeout < 0) {
                break;
            }
            $context->channel->pop($timeout);
        } while (count($context->stats) < $worker_num);

        $types = [
            'event_num'           => FormatterInterface::GAUGE,
            'signal_listener_num' => FormatterInterface::GAUGE,
            'aio_task_num'        => FormatterInterface::GAUGE,
            'aio_worker_num'      => FormatterInterface::GAUGE,
            'c_stack_size'        => FormatterInterface::GAUGE,
            'coroutine_num'       => FormatterInterface::GAUGE,
            'coroutine_peak_num'  => FormatterInterface::GAUGE,
            'coroutine_last_cid'  => FormatterInterface::GAUGE,
        ];

        ksort($context->stats);

        $str = '';
        foreach ($context->stats as $worker_id => $stats) {
            foreach ($types as $name => $type) {
                if (!isset($stats[$name])) {
                    continue;
                }

                if ($type === FormatterInterface::GAUGE) {
                    $str .= $this->formatter->gauge(
                        'swoole_coroutine_stats_' . $name, $stats[$name], ['worker_id' => $worker_id]
                    );
                }
            }
        }

        return $str;
    }
}