<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Metrics\WorkerCollectorInterface;
use ManaPHP\Redis\Event\RedisCalled;

class RedisCommandDurationCollector implements WorkerCollectorInterface
{
    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;

    #[Autowired] protected array $buckets = [0.001, 11];
    #[Autowired] protected array $ignored_keys = [];

    protected array $histograms = [];

    public function getContext(int $cid = 0): RedisCommandDurationCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updating(?string $handler): ?array
    {
        $context = $this->getContext();

        return ($handler !== null && $context->commands !== []) ? [$handler, $context->commands] : null;
    }

    public function updated($data): void
    {
        list($handler, $commands) = $data;
        foreach ($commands as list($command, $elapsed)) {
            if (($histogram = $this->histograms[$handler][$command] ?? null) === null) {
                $histogram = $this->histograms[$handler][$command] = new Histogram($this->buckets);
            }
            $histogram->update($elapsed);
        }
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

    public function querying(): array
    {
        return $this->histograms;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->histogram('app_redis_command_duration_seconds', $data, [], ['handler', 'command']);
    }
}