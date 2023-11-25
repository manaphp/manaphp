<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextTrait;
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
    use ContextTrait;
    use WorkersTrait;

    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected array $buckets = [1 << 8, 1 << 10, 1 << 12, 1 << 14, 1 << 16, 1 << 18, 1 << 20];
    #[Autowired] protected int $tasker_id = 0;

    protected array $histograms = [];

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
            /** @var RedisGetResponseSizeCollectorContext $context */
            $context = $this->getContext();

            $context->commands[] = \is_string($event->return) ? \strlen($event->return) : 0;
        }
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            /** @var RedisGetResponseSizeCollectorContext $context */
            $context = $this->getContext();

            $this->task($this->tasker_id)->updateRequest($handler, $context->commands);
        }
    }

    public function export(): string
    {
        $histograms = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->histogram('app_redis_get_response_size_bytes', $histograms, [], ['handler']
        );
    }
}