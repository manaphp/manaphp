<?php

namespace ManaPHP\Message;

/**
 * Interface ManaPHP\Message\QueueInterface
 *
 * @package messageQueue
 */
interface QueueInterface
{
    const PRIORITY_HIGHEST = 1;
    const PRIORITY_NORMAL = 5;
    const PRIORITY_LOWEST = 9;

    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     *
     * @return void
     */
    public function push($topic, $body, $priority = self::PRIORITY_NORMAL);

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