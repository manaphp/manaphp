<?php

namespace ManaPHP\Message;

use ManaPHP\Component;

abstract class Queue extends Component implements QueueInterface
{
    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     *
     * @return void
     */
    abstract public function do_push($topic, $body, $priority = Queue::PRIORITY_NORMAL);

    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     */
    public function push($topic, $body, $priority = self::PRIORITY_NORMAL)
    {
        $this->fireEvent('messageQueue:push', ['topic' => $topic]);

        $this->do_push($topic, $body, $priority);
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    abstract public function do_pop($topic, $timeout = PHP_INT_MAX);

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    public function pop($topic, $timeout = PHP_INT_MAX)
    {
        if (($msg = $this->do_pop($topic, $timeout)) !== false) {
            $this->fireEvent('messageQueue:pop', ['topic' => $topic, 'msg' => $msg]);
        }

        return $msg;
    }

    /**
     * @param string $topic
     *
     * @return void
     */
    abstract public function do_delete($topic);

    /**
     * @param string $topic
     *
     * @return void
     */
    public function delete($topic)
    {
        $this->fireEvent('messageQueue:delete', ['topic' => $topic]);
        $this->do_delete($topic);
    }

    /**
     * @param string $topic
     * @param int    $priority
     *
     * @return int
     */
    abstract public function do_length($topic, $priority = null);

    /**
     * @param string $topic
     * @param int    $priority
     *
     * @return         int
     */
    public function length($topic, $priority = null)
    {
        return $this->do_length($topic, $priority);
    }
}