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

class RedisGetResponseSizeCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected array $buckets = [1 << 8, 1 << 10, 1 << 12, 1 << 14, 1 << 16, 1 << 18, 1 << 20];
    #[Autowired] protected int $tasker_id = 0;
    #[Autowired] protected ?string $ignored_keys;

    protected array $histograms = [];

    public function getContext(int $cid = 0): RedisGetResponseSizeCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function updateRequest(string $handler, array $commands): void
    {
        foreach ($commands as $size) {
            if (($histogram = $this->histograms[$handler] ?? null) === null) {
                $histogram = $this->histograms[$handler] = new Histogram($this->buckets);
            }
            $histogram->update($size);
        }
    }

    public function getResponse(): array
    {
        return $this->histograms;
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

        return $this->formatter->histogram('app_redis_get_response_size_bytes', $histograms, [], ['handler']
        );
    }
}