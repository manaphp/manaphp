<?php
namespace ManaPHP\Message\Queue\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Message\Queue;

class Redis extends Queue
{
    /**
     * @var string|\Redis
     */
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_prefix = 'message_queue:';

    /**
     * @var int[]
     */
    protected $_priorities = [Queue::PRIORITY_HIGHEST, Queue::PRIORITY_NORMAL, Queue::PRIORITY_LOWEST];

    /**
     * @var array[]
     */
    protected $_topicKeys = [];

    /**
     * Redis constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['redis'])) {
            $this->_redis = $options['redis'];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }

        if (isset($options['priorities'])) {
            $this->_priorities = (array)$options['priorities'];
        }
    }

    /**
     * @return \Redis
     */
    protected function _getRedis()
    {
        if (strpos($this->_redis, '/') !== false) {
            return $this->_redis = $this->_di->getInstance('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_di->getShared($this->_redis);
        }
    }

    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     */
    public function do_push($topic, $body, $priority = Queue::PRIORITY_NORMAL)
    {
        if (!in_array($priority, $this->_priorities, true)) {
            throw new MisuseException(['`:priority` priority of `:topic is invalid`', 'priority' => $priority, 'topic' => $topic]);
        }

        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->lPush($this->_prefix . $topic . ':' . $priority, $body);
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    public function do_pop($topic, $timeout = PHP_INT_MAX)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        if (!isset($this->_topicKeys[$topic])) {
            $keys = [];
            foreach ($this->_priorities as $priority) {
                $keys[] = $this->_prefix . $topic . ':' . $priority;
            }

            $this->_topicKeys[$topic] = $keys;
        }
        if ($timeout === 0) {
            foreach ($this->_topicKeys[$topic] as $key) {
                $r = $redis->rPop($key);
                if ($r !== false) {
                    return $r;
                }
            }

            return false;
        } else {
            $r = $redis->brPop($this->_topicKeys[$topic], $timeout);
            return $r[1] ?? false;
        }
    }

    /**
     * @param string $topic
     *
     * @return void
     */
    public function do_delete($topic)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        foreach ($this->_priorities as $priority) {
            $redis->delete($this->_prefix . $topic . ':' . $priority);
        }
    }

    /**
     * @param string $topic
     * @param int    $priority
     *
     * @return         int
     */
    public function do_length($topic, $priority = null)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        if ($priority === null) {
            $length = 0;
            foreach ($this->_priorities as $p) {
                $length += $redis->lLen($this->_prefix . $topic . ':' . $p);
            }

            return $length;
        } else {
            return $redis->lLen($this->_prefix . $topic . ':' . $priority);
        }
    }
}
