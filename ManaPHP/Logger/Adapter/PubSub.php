<?php
namespace ManaPHP\Logger\Adapter;

use ManaPHP\Logger;

class PubSub extends Logger
{
    /**
     * @var string
     */
    protected $_channel;

    /**
     * Redis constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->_channel = $options['channel'] ?? 'logger';
    }

    /**
     * @param \ManaPHP\Logger\Log[] $logs
     */
    public function append($logs)
    {
        $this->redis->call('publish', $this->_channel . ':' . $this->configure->id, json_stringify($logs));
    }
}
