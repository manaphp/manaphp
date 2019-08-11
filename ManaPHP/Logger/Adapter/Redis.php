<?php
namespace ManaPHP\Logger\Adapter;

use ManaPHP\Logger;

class Redis extends Logger
{
    /**
     * @var string
     */
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_key = 'log';

    /**
     * Redis constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        if (isset($options['redis'])) {
            $this->_redis = $options['redis'];
        }

        if (isset($options['key'])) {
            $this->_key = $options['key'];
        }
    }

    /**
     * @param \ManaPHP\Logger\Log $log
     */
    public function append($log)
    {
        $data = [
            'date' => date('Y-m-d\TH:i:s', $log->timestamp) . sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000),
            '@timestamp' => $log->timestamp,
            'host' => $log->host,
            'category' => $log->category,
            'level' => $log->level,
            'location' => "$log->file:$log->line",
            'message' => $log->message];

        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        $this->_redis->rPush($this->_key, json_stringify($data));
    }
}