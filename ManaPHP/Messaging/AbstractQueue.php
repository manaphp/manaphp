<?php

namespace ManaPHP\Messaging;

use ManaPHP\Component;

abstract class AbstractQueue extends Component implements QueueInterface
{
    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     *
     * @return void
     */
    abstract public function do_push($topic, $body, $priority = self::PRIORITY_NORMAL);

    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     */
    public function push($topic, $body, $priority = self::PRIORITY_NORMAL)
    {
        $this->fireEvent('msgQueue:push', compact('topic', 'body', 'priority'));

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
            $this->fireEvent('msgQueue:pop', compact('topic', 'msg'));
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
        $this->fireEvent('msgQueue:delete', compact('topic'));
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