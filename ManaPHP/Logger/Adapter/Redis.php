<?php
namespace ManaPHP\Logger\Adapter;

use ManaPHP\Logger;

class Redis extends Logger
{
    /**
     * @var string
     */
    protected $_key;

    /**
     * Redis constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->_key = $options['key'] ?? "cache:{$this->configure->id}:logger";
    }

    /**
     * @param \ManaPHP\Logger\Log[] $logs
     */
    public function append($logs)
    {
        foreach ($logs as $log) {
            $data = [
                'date' => date('Y-m-d\TH:i:s', $log->timestamp) . sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000),
                '@timestamp' => $log->timestamp,
                'host' => $log->host,
                'category' => $log->category,
                'level' => $log->level,
                'location' => "$log->file:$log->line",
                'message' => $log->message];
            $this->redisBroker->rPush($this->_key, json_stringify($data));
        }
    }
}
