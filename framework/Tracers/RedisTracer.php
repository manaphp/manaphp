<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class RedisTracer extends Tracer
{
    #[Inject] protected ConfigInterface $config;

    public function listen(): void
    {
        $this->verbose && $this->attachEvent('redis:connecting', [$this, 'onConnecting']);
        $this->attachEvent('redis:calling', [$this, 'onCalling']);
        $this->attachEvent('redis:called', [$this, 'onCalled']);
    }

    public function onConnecting(EventArgs $eventArgs): void
    {
        $this->debug(['connecting to `:uri`', 'uri' => $eventArgs->data['uri']], 'redis.connect');
    }

    public function onCalling(EventArgs $eventArgs): void
    {
        $method = $eventArgs->data['method'];
        $arguments = $eventArgs->data['arguments'];

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

    public function onCalled(EventArgs $eventArgs): void
    {
        $method = $eventArgs->data['method'];
        $arguments = $eventArgs->data['arguments'];
        foreach ($arguments as $k => $v) {
            if (is_string($v) && strlen($v) > 128) {
                $arguments[$k] = substr($v, 0, 128) . '...';
            }
        }

        if ($this->verbose) {
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $return = json_stringify($eventArgs->data['return'], JSON_PARTIAL_OUTPUT_ON_ERROR);
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