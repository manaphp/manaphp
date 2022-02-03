<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\AbstractLogger;

/**
 * @property-read \ManaPHP\ConfigInterface           $config
 * @property-read \ManaPHP\Data\RedisBrokerInterface $redisBroker
 */
class Redis extends AbstractLogger
{
    protected string $key;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->key = $options['key'] ?? sprintf("cache:%s:logger", $this->config->get("id"));
    }

    /**
     * @param \ManaPHP\Logging\Logger\Log[] $logs
     */
    public function append(array $logs): void
    {
        foreach ($logs as $log) {
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
}
