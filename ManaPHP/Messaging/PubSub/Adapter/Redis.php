<?php

namespace ManaPHP\Messaging\PubSub\Adapter;

use ManaPHP\Component;
use ManaPHP\Messaging\PubSubInterface;

class Redis extends Component implements PubSubInterface
{
    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['redisBroker'])) {
            $this->injections['redisBroker'] = $options['redisBroker'];
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function subscribe($channels, $callback)
    {
        $this->redisBroker->subscribe(
            $channels, static function ($redis, $channel, $msg) use ($callback) {
            $callback($channel, $msg);
        }
        );
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     *
     * @param array    $patterns
     * @param callable $callback
     *
     * @return void
     */
    public function psubscribe($patterns, $callback)
    {
        $this->redisBroker->psubscribe(
            $patterns, static function ($redis, $pattern, $channel, $msg) use ($callback) {
            $callback($channel, $msg);
        }
        );
    }

    /**
     * @param string $channel
     * @param string $message
     *
     * @return int
     */
    public function publish($channel, $message)
    {
        return $this->redisBroker->publish($channel, $message);
    }

    /**
     * @param array $channels
     *
     * @return void
     */
    public function unsubscribe($channels = null)
    {
        $this->redisBroker->unsubscribe($channels);
    }

    /**
     * @param array $patterns
     *
     * @return void
     */
    public function punsubscribe($patterns = null)
    {
        $this->redisBroker->punsubscribe($patterns);
    }
}
