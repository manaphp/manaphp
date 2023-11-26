<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Swoole\WorkersTrait;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class MemoryUsageCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;

    public function getContext(int $cid = 0): MemoryUsageCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function getWorkerRequest(int $cid, $worker_id): void
    {
        $this->sendMessage($worker_id)->getWorkerResponse(
            $cid, $worker_id, [memory_get_usage(), memory_get_peak_usage()]
        );
    }

    public function getWorkerResponse(int $cid, int $worker_id, array $stats): void
    {
        $context = $this->getContext($cid);
        $context->messages[$worker_id] = $stats;
        $context->channel->push(1);
    }

    public function getTaskResponse(): array
    {
        return [memory_get_usage(), memory_get_peak_usage()];
    }

    public function export(mixed $data): string
    {
        $context = $this->getContext();
        $worker_num = $this->workers->getWorkerNum();
        $context->channel = new Channel($worker_num);
        $context->messages[$this->workers->getWorkerId()] = [memory_get_usage(), memory_get_peak_usage()];
        $context->channel->push(1);

        $my_worker_id = $this->workers->getWorkerId();
        for ($worker_id = 0; $worker_id < $worker_num; $worker_id++) {
            if ($my_worker_id !== $worker_id) {
                $this->sendMessage($worker_id)->getWorkerRequest(Coroutine::getCid(), $my_worker_id);
            }
        }

        $end_time = microtime(true) + 0.3;
        do {
            $timeout = $end_time - microtime(true);
            if ($timeout < 0) {
                break;
            }
            $context->channel->pop($timeout);
        } while (\count($context->messages) < $worker_num);

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
            $stats = $this->task($task_worker_id, 0.1)->getTaskResponse();
            $labels = ['worker_id' => $worker_num + $task_worker_id, 'task_worker_id' => $task_worker_id];

            $str .= $this->formatter->gauge('swoole_task_worker_memory_usage', $stats[0], $labels);
            $str .= $this->formatter->gauge('swoole_task_worker_memory_peak_usage', $stats[1], $labels);
        }

        return $str;
    }
}