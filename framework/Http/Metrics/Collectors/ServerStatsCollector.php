<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Http\Metrics\AbstractCollector;
use ManaPHP\Http\Metrics\FormatterInterface;

class ServerStatsCollector extends AbstractCollector
{
    public function export(): string
    {
        $stats = $this->server->stats();
        $types = [
            'start_time'           => FormatterInterface::GAUGE,
            'connection_num'       => FormatterInterface::GAUGE,
            'abort_count'          => FormatterInterface::COUNTER,
            'accept_count'         => FormatterInterface::COUNTER,
            'close_count'          => FormatterInterface::COUNTER,
            'worker_num'           => FormatterInterface::GAUGE,
            'task_worker_num'      => FormatterInterface::GAUGE,
            'user_worker_num'      => FormatterInterface::GAUGE,
            'idle_worker_num'      => FormatterInterface::GAUGE,
            'dispatch_count'       => FormatterInterface::COUNTER,
            'request_count'        => FormatterInterface::COUNTER,
            'response_count'       => FormatterInterface::COUNTER,
            'total_recv_bytes'     => FormatterInterface::COUNTER,
            'total_send_bytes'     => FormatterInterface::COUNTER,
            'pipe_packet_msg_id'   => FormatterInterface::GAUGE,
            'session_round'        => FormatterInterface::GAUGE,
            'min_fd'               => FormatterInterface::GAUGE,
            'max_fd'               => FormatterInterface::GAUGE,
            'task_idle_worker_num' => FormatterInterface::GAUGE,
            'tasking_num'          => FormatterInterface::GAUGE,
            'coroutine_num'        => FormatterInterface::GAUGE,
            'coroutine_peek_num'   => FormatterInterface::GAUGE,
            'task_queue_num'       => FormatterInterface::GAUGE,
            'task_queue_bytes'     => FormatterInterface::GAUGE,
        ];

        $str = '';
        foreach ($types as $name => $type) {
            if (!isset($stats[$name])) {
                continue;
            }

            if ($type === FormatterInterface::GAUGE) {
                $str .= $this->formatter->gauge('swoole_server_stats_' . $name, $stats[$name]);
            } else {
                $str .= $this->formatter->counter('swoole_server_stats_' . $name, $stats[$name]);
            }
        }

        return $str;
    }
}