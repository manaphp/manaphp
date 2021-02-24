<?php

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\Logger;

/**
 * @property-read \Redis $redisBroker
 */
class Redis extends Logger
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['redisBroker'])) {
            $this->injections['redisBroker'] = $options['redisBroker'];
        }

        parent::__construct($options);

        $this->key = $options['key'] ?? sprintf("cache:%s:logger", APP_ID);
    }

    /**
     * @param \ManaPHP\Logging\Logger\Log[] $logs
     */
    public function append($logs)
    {
        foreach ($logs as $log) {
            $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
            $data = [
                'date'       => date('Y-m-d\TH:i:s', $log->timestamp) . $ms,
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
