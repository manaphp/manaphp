<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Redis\Event\RedisCalled;
use ManaPHP\Redis\Event\RedisCalling;
use ManaPHP\Redis\Event\RedisConnecting;
use Psr\Log\LoggerInterface;

class RedisTracer
{
    #[Inject] protected LoggerInterface $logger;
    #[Inject] protected ConfigInterface $config;

    #[Value] protected bool $verbose = true;

    public function onConnecting(#[Event] RedisConnecting $event): void
    {
        if ($this->verbose) {
            $this->logger->debug('connecting to {0}', [$event->uri, 'category' => 'redis.connect']);
        }
    }

    public function onCalling(#[Event] RedisCalling $event): void
    {
        $method = $event->method;
        $arguments = $event->arguments;

        $args = substr(json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR), 1, -1);
        if (stripos(',blPop,brPop,brpoplpush,subscribe,psubscribe,', ",$method,") !== false) {
            $this->logger->debug("\$redis->$method({0}) ... blocking", [$args, 'category' => 'redis.' . $method]);
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

            $ret = strlen($return) > 64 ? substr($return, 0, 64) . '...' : $return;
            $args = strlen($arguments) > 256 ? substr($arguments, 1, 256) . '...)' : substr($arguments, 1, -1);
            $this->logger->debug("\$redis->$method({0}) => {1}", [$args, $ret, 'category' => 'redis.' . $method]);
        } else {
            $key = $arguments[0] ?? false;
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!$this->config->get('debug') && is_string($key) && str_starts_with($key, 'cache:')) {
                return;
            }
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);

            $args = strlen($arguments) > 256 ? substr($arguments, 1, 256) . '...)' : substr($arguments, 1, -1);
            $this->logger->debug("\$redis->$method({0})", [$args, 'category' => 'redis.' . $method]);
        }
    }
}