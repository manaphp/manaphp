<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Redis\Event\RedisCalled;
use ManaPHP\Redis\Event\RedisCalling;
use ManaPHP\Redis\Event\RedisConnecting;
use ManaPHP\Tracer;

class RedisTracer extends Tracer
{
    #[Inject] protected ConfigInterface $config;

    public function onConnecting(#[Event] RedisConnecting $event): void
    {
        if ($this->verbose) {
            $this->debug(['connecting to `:uri`', 'uri' => $event->uri], 'redis.connect');
        }
    }

    public function onCalling(#[Event] RedisCalling $event): void
    {
        $method = $event->method;
        $arguments = $event->arguments;

        if (stripos(',blPop,brPop,brpoplpush,subscribe,psubscribe,', ",$method,") !== false) {
            $this->debug(
                sprintf(
                    "\$redis->$method(%s) ... blocking",
                    substr(json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR), 1, -1)
                ),
                'redis.' . $method
            );
        }
    }

    public function onCalled(#[Event] RedisCalled $event): void
    {
        $method = $event->method;
        $arguments = $event->arguments;
        foreach ($arguments as $k => $v) {
            if (is_string($v) && strlen($v) > 128) {
                $arguments[$k] = substr($v, 0, 128) . '...';
            }
        }

        if ($this->verbose) {
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $return = json_stringify($event->return, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $this->debug(
                sprintf(
                    "\$redis->$method(%s) => %s",
                    strlen($arguments) > 256 ? substr($arguments, 1, 256) . '...)' : substr($arguments, 1, -1),
                    strlen($return) > 64 ? substr($return, 0, 64) . '...' : $return
                ),
                'redis.' . $method
            );
        } else {
            $key = $arguments[0] ?? false;
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!$this->config->get('debug') && is_string($key) && str_starts_with($key, 'cache:')) {
                return;
            }
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $this->debug(
                sprintf(
                    "\$redis->$method(%s)",
                    strlen($arguments) > 256 ? substr($arguments, 1, 256) . '...)' : substr($arguments, 1, -1)
                ),
                'redis.' . $method
            );
        }
    }
}