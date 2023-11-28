<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\WorkersCollectorInterface;
use Swoole\Coroutine;

class CoroutineStatsCollector implements WorkersCollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;

    public function querying(): array
    {
        return Coroutine::stats();
    }

    public function export(mixed $data): string
    {
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

        $str = '';
        foreach ($types as $name => $type) {
            foreach ($data as $worker_id => $stats) {
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