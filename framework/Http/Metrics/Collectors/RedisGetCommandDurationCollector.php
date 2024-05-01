<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Metrics\WorkerCollectorInterface;
use ManaPHP\Redis\Event\RedisCalled;
use function preg_match;

class RedisGetCommandDurationCollector implements WorkerCollectorInterface
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected FormatterInterface $formatter;

    #[Autowired] protected array $buckets = [0.001, 11];
    #[Autowired] protected ?string $ignored_keys;

    protected array $histograms = [];

    public function getContext(int $cid = 0): RedisGetCommandDurationCollectorContext
    {
        return $this->contextManager->getContext($this, $cid);
    }

    public function updating(?string $handler): ?array
    {
        $context = $this->getContext();
        return ($handler !== null && $context->commands !== []) ? [$handler, $context->commands] : null;
    }

    public function updated(array $data): void
    {
        list($handler, $commands) = $data;

        foreach ($commands as $elapsed) {
            if (($histogram = $this->histograms[$handler] ?? null) === null) {
                $histogram = $this->histograms[$handler] = new Histogram($this->buckets);
            }
            $histogram->update($elapsed);
        }
    }

    public function onRedisCalled(#[Event] RedisCalled $event): void
    {
        $method = $event->method;
        if ($method === 'get' || $method === 'hGet') {
            if ($this->ignored_keys === null || preg_match($this->ignored_keys, $event->arguments[0]) !== 1) {
                $context = $this->getContext();

                $context->commands[] = $event->elapsed;
            }
        }
    }

    public function querying(): array
    {
        return $this->histograms;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->histogram('app_redis_get_command_duration_seconds', $data, [], ['handler']);
    }
}