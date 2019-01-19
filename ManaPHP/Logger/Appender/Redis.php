<?php
namespace ManaPHP\Logger\Appender;

use ManaPHP\Component;
use ManaPHP\Logger\AppenderInterface;

class Redis extends Component implements AppenderInterface
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
     * @param string|array $options
     */
    public function __construct($options = 'redis')
    {
        if (is_string($options)) {
            $this->_redis = $options;
        } else {
            if (isset($options['redis'])) {
                $this->_redis = $options['redis'];
            }

            if (isset($options['key'])) {
                $this->_key = $options['key'];
            }
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

        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->rPush($this->_key, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return \ManaPHP\Redis
     */
    protected function _getRedis()
    {
        if (strpos($this->_redis, '/') !== false) {
            return $this->_redis = $this->_di->getInstance('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_di->getShared($this->_redis);
        }
    }
}