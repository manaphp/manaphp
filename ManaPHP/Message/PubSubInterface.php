<?php
namespace ManaPHP\Message;

interface PubSubInterface
{
    /**
     * @param string[] $channels
     * @param callable $callback The callback will get two arguments ($channel, $message)
     *
     * @return void
     */
    public function subscribe($channels, $callback);

    /**
     * @param array    $patterns
     * @param callable $callback The callback will get two arguments ($channel, $message)
     *
     * @param void
     */
    public function psubscribe($patterns, $callback);

    /**
     * @param string $channel
     * @param string $message
     *
     * @return int Number of clients that received the message
     */
    public function publish($channel, $message);

    /**
     * @param array $channels
     */
    public function unsubscribe($channels = null);

    /**
     * @param array $patterns
     */
    public function punsubscribe($patterns = null);
}
