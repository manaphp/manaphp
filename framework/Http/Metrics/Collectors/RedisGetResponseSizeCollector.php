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

class RedisGetResponseSizeCollector implements WorkerCollectorInterface
{
    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;

    #[Autowired] protected array $buckets = [1024, 11];
    #[Autowired] protected ?string $ignored_keys;

    protected array $histograms = [];

    public function getContext(int $cid = 0): RedisGetResponseSizeCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updating(?string $handler): ?array
    {
        $context = $this->getContext();

        return ($handler !== null && $context->commands !== []) ? [$handler, $context->commands] : null;
    }

    public function updated(array $data): void
    {
        list($handler, $commands) = $data;

        foreach ($commands as $size) {
            if (($histogram = $this->histograms[$handler] ?? null) === null) {
                $histogram = $this->histograms[$handler] = new Histogram($this->buckets);
            }
            $histogram->update($size);
        }
    }

    public function onRedisCalled(#[Event] RedisCalled $event): void
    {
        $method = $event->method;
        if ($method === 'get' || $method === 'hGet') {
            if ($this->ignored_keys === null || \preg_match($this->ignored_keys, $event->arguments[0]) !== 1) {
                $context = $this->getContext();

                $context->commands[] = \is_string($event->return) ? \strlen($event->return) : 0;
            }
        }
    }

    public function querying(): array
    {
        return $this->histograms;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->histogram('app_redis_get_response_size_bytes', $data, [], ['handler']);
    }
}