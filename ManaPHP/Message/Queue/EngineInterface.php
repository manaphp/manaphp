<?php
namespace ManaPHP\Message\Queue;

use ManaPHP\Message\Queue;

interface EngineInterface
{
    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     *
     * @return void
     */
    public function push($topic, $body, $priority = Queue::PRIORITY_NORMAL);

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    public function pop($topic, $timeout = PHP_INT_MAX);

    /**
     * @param string $topic
     *
     * @return void
     */
    public function delete($topic);

    /**
     * @param string $topic
     * @param int    $priority
     *
     * @return int
     */
    public function length($topic, $priority = null);
}