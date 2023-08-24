<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Logger\Log;
use ManaPHP\Redis\RedisBrokerInterface;

class Redis extends AbstractLogger
{
    #[Inject] protected ConfigInterface $config;
    #[Inject] protected RedisBrokerInterface $redisBroker;

    #[Value] protected ?string $key;

    public function append(Log $log): void
    {
        $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
        $data = [
            'date'       => date('Y-m-d\TH:i:s', (int)$log->timestamp) . $ms,
            '@timestamp' => $log->timestamp,
            'hostname'   => $log->hostname,
            'category'   => $log->category,
            'level'      => $log->level,
            'location'   => $log->location,
            'message'    => $log->message
        ];
        $this->redisBroker->rPush(
            $this->key ?? sprintf("cache:%s:logger", $this->config->get("id")),
            json_stringify($data)
        );
    }
}
