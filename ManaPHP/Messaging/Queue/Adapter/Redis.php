<?php

namespace ManaPHP\Messaging\Queue\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Messaging\Queue;

class Redis extends Queue
{
    /**
     * @var string
     */
    protected $_prefix;

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
        $this->_prefix = $options['prefix'] ?? 'cache:messageQueue:';

        if (isset($options['priorities'])) {
            $this->_priorities = (array)$options['priorities'];
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
            throw new MisuseException(['`%d` priority of `%s` is invalid', $priority, $topic]);
        }

        $this->redisBroker->lPush($this->_prefix . $topic . ':' . $priority, $body);
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    public function do_pop($topic, $timeout = PHP_INT_MAX)
    {
        if (!isset($this->_topicKeys[$topic])) {
            $keys = [];
            foreach ($this->_priorities as $priority) {
                $keys[] = $this->_prefix . $topic . ':' . $priority;
            }

            $this->_topicKeys[$topic] = $keys;
        }


        if ($timeout === 0) {
            foreach ($this->_topicKeys[$topic] as $key) {
                $r = $this->redisBroker->rPop($key);
                if ($r !== false) {
                    return $r;
                }
            }

            return false;
        } else {
            $r = $this->redisBroker->brPop($this->_topicKeys[$topic], $timeout);
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
        foreach ($this->_priorities as $priority) {
            $this->redisBroker->del($this->_prefix . $topic . ':' . $priority);
        }
    }

    /**
     * @param string $topic
     * @param int    $priority
     *
     * @return int
     */
    public function do_length($topic, $priority = null)
    {
        if ($priority === null) {
            $length = 0;
            foreach ($this->_priorities as $p) {
                $length += $this->redisBroker->lLen($this->_prefix . $topic . ':' . $p);
            }

            return $length;
        } else {
            return $this->redisBroker->lLen($this->_prefix . $topic . ':' . $priority);
        }
    }
}
