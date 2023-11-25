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

class RedisGetCommandDurationCollector implements CollectorInterface
{
    use ContextTrait;
    use WorkersTrait;

    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected array $buckets = ['0.001', '0.005', '0.01', '0.05', '0.1', '0.5', '1'];
    #[Autowired] protected int $tasker_id = 0;

    protected array $histograms = [];

    public function updateRequest(string $handler, array $commands): void
    {
        foreach ($commands as $elapsed) {
            if (($histogram = $this->histograms[$handler] ?? null) === null) {
                $histogram = $this->histograms[$handler] = new Histogram($this->buckets);
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
        $method = $event->method;
        if ($method === 'get' || $method === 'hGet') {
            /** @var RedisGetCommandDurationCollectorContext $context */
            $context = $this->getContext();

            $context->commands[] = $event->elapsed;
        }
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            /** @var RedisGetCommandDurationCollectorContext $context */
            $context = $this->getContext();

            $this->task($this->tasker_id)->updateRequest($handler, $context->commands);
        }
    }

    public function export(): string
    {
        $histograms = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->histogram('app_redis_get_command_duration_seconds', $histograms, [], ['handler']
        );
    }
}