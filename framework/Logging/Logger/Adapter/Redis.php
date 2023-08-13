<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\ConfigInterface;
use ManaPHP\Data\RedisBrokerInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Level;
use ManaPHP\Logging\Logger\Log;

class Redis extends AbstractLogger
{
    #[Inject] protected ConfigInterface $config;
    #[Inject] protected RedisBrokerInterface $redisBroker;

    protected string $key;

    public function __construct(?string $key = null, string $level = Level::DEBUG, ?string $hostname = null)
    {
        parent::__construct($level, $hostname);

        $this->key = $key ?? sprintf("cache:%s:logger", $this->config->get("id"));
    }

    public function append(Log $log): void
    {
        $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
        $data = [
            'date'       => date('Y-m-d\TH:i:s', (int)$log->timestamp) . $ms,
            '@timestamp' => $log->timestamp,
            'hostname'   => $log->hostname,
            'category'   => $log->category,
            'level'      => $log->level,
            'location'   => "$log->file:$log->line",
            'message'    => $log->message
        ];
        $this->redisBroker->rPush($this->key, json_stringify($data));
    }
}
