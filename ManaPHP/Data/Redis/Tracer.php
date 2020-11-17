<?php

namespace ManaPHP\Data\Redis;

use ManaPHP\Event\EventArgs;

class Tracer extends \ManaPHP\Event\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->_verbose && $this->attachEvent('redis:connect', [$this, 'onConnect']);
        $this->attachEvent('redis:calling', [$this, 'onCalling']);
        $this->attachEvent('redis:called', [$this, 'onCalled']);
    }

    public function onConnect(EventArgs $eventArgs)
    {
        $this->logger->debug(['connect to `:url`', 'url' => $eventArgs->data], 'redis.connect');
    }

    public function onCalling(EventArgs $eventArgs)
    {
        $method = $eventArgs->data['method'];
        $arguments = $eventArgs->data['arguments'];

        if (stripos(',blPop,brPop,brpoplpush,subscribe,psubscribe,', ",$method,") !== false) {
            $this->logger->debug(
                [
                    "\$redis->$method(:args) ... blocking",
                    'args' => substr(json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR), 1, -1),
                ], 'redis.' . $method
            );
        }
    }

    public function onCalled(EventArgs $eventArgs)
    {
        $method = $eventArgs->data['method'];
        $arguments = $eventArgs->data['arguments'];
        foreach ($arguments as $k => $v) {
            if (is_string($v) && strlen($v) > 128) {
                $arguments[$k] = substr($v, 0, 128) . '...';
            }
        }

        if ($this->_verbose) {
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $return = json_stringify($eventArgs->data['return'], JSON_PARTIAL_OUTPUT_ON_ERROR);
            $this->logger->debug(
                [
                    "\$redis->$method(:args) => :return",
                    'args'   => strlen($arguments) > 256
                        ? substr($arguments, 1, 256) . '...)'
                        : substr(
                            $arguments, 1, -1
                        ),
                    'return' => strlen($return) > 64 ? substr($return, 0, 64) . '...' : $return
                ], 'redis.' . $method
            );
        } else {
            $key = $arguments[0] ?? false;
            if (!$this->configure->debug && is_string($key) && str_starts_with($key, 'cache:')) {
                return;
            }
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $this->logger->debug(
                [
                    "\$redis->$method(:args)",
                    'args' => strlen($arguments) > 256
                        ? substr($arguments, 1, 256) . '...)'
                        : substr(
                            $arguments, 1, -1
                        ),
                ], 'redis.' . $method
            );
        }

    }
}