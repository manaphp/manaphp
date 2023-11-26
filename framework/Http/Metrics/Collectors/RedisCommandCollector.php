<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Redis\Event\RedisCalled;
use ManaPHP\Swoole\WorkersTrait;

class RedisCommandCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected array $buckets
        = ['0.001', '0.002', '0.004', '0.008', '0.016', '0.032', '0.064', '0.128', '0.256', '0.512', '1.024'];
    #[Autowired] protected int $tasker_id = 0;
    #[Autowired] protected array $ignored_keys = [];

    protected array $histograms = [];

    public function getContext(int $cid = 0): RedisCommandCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updateRequest(string $handler, array $commands): void
    {
        foreach ($commands as list($command, $elapsed)) {
            if (($histogram = $this->histograms[$handler][$command] ?? null) === null) {
                $histogram = $this->histograms[$handler][$command] = new Histogram($this->buckets);
            }
            $histogram->update($elapsed);
        }
    }

    public function getResponse(): array
    {
        return $this->histograms;
    }

    public function onRedisCalled(#[Event] RedisCalled $event): void
    {
        if (($ignored_key = $this->ignored_keys[$event->method] ?? null) === null
            || (\is_string($ignored_key) && \preg_match($ignored_key, $event->arguments[0]) !== 1)
            || (\is_array($ignored_key) && \preg_match($ignored_key[0], $event->arguments[$ignored_key[1]]) !== 1)
        ) {
            $context = $this->getContext();

            $context->commands[] = [strtolower($event->method), $event->elapsed];
        }
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            $context = $this->getContext();

            if ($context->commands !== []) {
                $this->task($this->tasker_id)->updateRequest($handler, $context->commands);
            }
        }
    }

    public function export(): string
    {
        $histograms = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->histogram('app_redis_command_duration_seconds', $histograms, [], ['handler', 'command']
        );
    }
}