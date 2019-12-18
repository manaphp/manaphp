<?php
namespace ManaPHP\Logger\Adapter;

use ManaPHP\Logger;

class PubSub extends Logger
{
    /**
     * @var string
     */
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_channel = 'logger';

    /**
     * Redis constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($options['redis'])) {
            $this->_redis = $options['redis'];
        }

        if (isset($options['channel'])) {
            $this->_channel = $options['channel'];
        }
    }

    /**
     * @param \ManaPHP\Logger\Log[] $logs
     */
    public function append($logs)
    {
        if (is_string($this->_redis)) {
            $this->_redis = $this->_di->getShared($this->_redis);
        }

        $this->redis->call('publish', $this->_channel . ':' . $this->configure->id, json_stringify($logs));
    }
}
