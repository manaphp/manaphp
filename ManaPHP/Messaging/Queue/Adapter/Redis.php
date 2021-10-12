<?php

namespace ManaPHP\Messaging\Queue\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Messaging\AbstractQueue;

/**
 * @property-read \Redis $redisBroker
 */
class Redis extends AbstractQueue
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var int[]
     */
    protected $priorities = [AbstractQueue::PRIORITY_HIGHEST, AbstractQueue::PRIORITY_NORMAL, AbstractQueue::PRIORITY_LOWEST];

    /**
     * @var array[]
     */
    protected $topicKeys = [];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->prefix = $options['prefix'] ?? 'cache:msgQueue:';

        if (isset($options['priorities'])) {
            $this->priorities = (array)$options['priorities'];
        }
    }

    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     */
    public function do_push($topic, $body, $priority = AbstractQueue::PRIORITY_NORMAL)
    {
        if (!in_array($priority, $this->priorities, true)) {
            throw new MisuseException(['`%d` priority of `%s` is invalid', $priority, $topic]);
        }

        $this->redisBroker->lPush($this->prefix . $topic . ':' . $priority, $body);
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    public function do_pop($topic, $timeout = PHP_INT_MAX)
    {
        if (!isset($this->topicKeys[$topic])) {
            $keys = [];
            foreach ($this->priorities as $priority) {
                $keys[] = $this->prefix . $topic . ':' . $priority;
            }

            $this->topicKeys[$topic] = $keys;
        }


        if ($timeout === 0) {
            foreach ($this->topicKeys[$topic] as $key) {
                $r = $this->redisBroker->rPop($key);
                if ($r !== false) {
                    return $r;
                }
            }

            return false;
        } else {
            $r = $this->redisBroker->brPop($this->topicKeys[$topic], $timeout);
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
        foreach ($this->priorities as $priority) {
            $this->redisBroker->del($this->prefix . $topic . ':' . $priority);
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
            foreach ($this->priorities as $p) {
                $length += $this->redisBroker->lLen($this->prefix . $topic . ':' . $p);
            }

            return $length;
        } else {
            return $this->redisBroker->lLen($this->prefix . $topic . ':' . $priority);
        }
    }
}
