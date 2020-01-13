<?php
namespace ManaPHP\Message\PubSub\Adapter;

use ManaPHP\Component;
use ManaPHP\Message\PubSubInterface;

class Redis extends Component implements PubSubInterface
{
    public function subscribe($channels, $callback)
    {
        $this->redis->subscribe($channels, static function ($redis, $chan, $msg) use ($callback) {
            $callback($chan, $msg);
        });
    }

    public function psubscribe($patterns, $callback)
    {
        $this->redis->psubscribe($patterns, static function ($redis, $pattern, $chan, $msg) use ($callback) {
            $callback($chan, $msg);
        });
    }

    public function publish($channel, $message)
    {
        return $this->redis->publish($channel, $message);
    }

    public function unsubscribe($channels = null)
    {
        $this->redis->unsubscribe($channels);
    }

    public function punsubscribe($patterns = null)
    {
        $this->redis->punsubscribe($patterns);
    }
}
