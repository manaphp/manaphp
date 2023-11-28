<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\WorkersCollectorInterface;
use ManaPHP\Swoole\WorkersTrait;

class MemoryUsageCollector implements WorkersCollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected FormatterInterface $formatter;

    public function querying(): array
    {
        return [memory_get_usage(), memory_get_peak_usage()];
    }

    public function export(mixed $data): string
    {
        $str = '';

        foreach (['memory_usage', 'memory_peek_usage'] as $index => $name) {
            foreach ($data as $worker_id => $stats) {
                $labels = ['worker_id' => $worker_id];
                $str .= $this->formatter->gauge('swoole_worker_' . $name, $stats[$index], $labels);
            }
        }

        $worker_num = $this->workers->getWorkerNum();
        $task_worker_num = $this->workers->getTaskWorkerNum();
        for ($task_worker_id = 0; $task_worker_id < $task_worker_num; $task_worker_id++) {
            $stats = $this->task($task_worker_id, 1)->querying();
            $labels = ['worker_id' => $worker_num + $task_worker_id, 'task_worker_id' => $task_worker_id];

            $str .= $this->formatter->gauge('swoole_task_worker_memory_usage', $stats[0], $labels);
            $str .= $this->formatter->gauge('swoole_task_worker_memory_peak_usage', $stats[1], $labels);
        }

        return $str;
    }
}