<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Swoole\WorkersInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class MemoryUsageCollector implements CollectorInterface
{
    use ContextTrait;

    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected WorkersInterface $workers;

    public function taskExportRequest(int $cid, $worker_id): void
    {
        $this->workers->sendMessage([$this, 'taskExportResponse'],
            [$cid, $worker_id, [memory_get_usage(), memory_get_peak_usage()]],
            $worker_id);
    }

    public function taskExportResponse(int $cid, int $worker_id, array $stats): void
    {
        /** @var MemoryUsageCollectorContext $context */
        $context = $this->getContext($cid);
        $context->messages[$worker_id] = $stats;
        $context->channel->push(1);
    }

    public function taskExport(): array
    {
        return [memory_get_usage(), memory_get_peak_usage()];
    }

    public function export(): string
    {
        /** @var MemoryUsageCollectorContext $context */
        $context = $this->getContext();
        $worker_num = $this->workers->getWorkerNum();
        $context->channel = new Channel($worker_num);
        $context->messages[$this->workers->getWorkerId()] = [memory_get_usage(), memory_get_peak_usage()];
        $context->channel->push(1);

        for ($worker_id = 0; $worker_id < $worker_num; $worker_id++) {
            if ($this->workers->getWorkerId() !== $worker_id) {
                $arguments = [Coroutine::getCid()];
                $this->workers->sendMessage([$this, 'taskExportRequest'], $arguments, $worker_id);
            }
        }

        $end_time = microtime(true) + 0.3;
        do {
            $timeout = $end_time - microtime(true);
            if ($timeout < 0) {
                break;
            }
            $context->channel->pop($timeout);
        } while (count($context->messages) < $worker_num);

        ksort($context->messages);

        $str = '';
        foreach ($context->messages as $worker_id => $stats) {
            $labels = ['worker_id' => $worker_id];
            $str .= $this->formatter->gauge('swoole_worker_memory_usage', $stats[0], $labels);
            $str .= $this->formatter->gauge('swoole_worker_memory_peak_usage', $stats[1], $labels);
        }

        $worker_num = $this->workers->getWorkerNum();
        $task_worker_num = $this->workers->getTaskWorkerNum();
        for ($task_worker_id = 0; $task_worker_id < $task_worker_num; $task_worker_id++) {
            $stats = $this->workers->taskwait([$this, 'taskExport'], [], 0.1, $task_worker_id);
            $labels = ['worker_id' => $worker_num + $task_worker_id, 'task_worker_id' => $task_worker_id];

            $str .= $this->formatter->gauge('swoole_task_worker_memory_usage', $stats[0], $labels);
            $str .= $this->formatter->gauge('swoole_task_worker_memory_peak_usage', $stats[1], $labels);
        }

        return $str;
    }
}