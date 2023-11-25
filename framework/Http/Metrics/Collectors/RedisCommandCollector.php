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
use ManaPHP\Redis\Event\RedisCalling;
use ManaPHP\Swoole\WorkersTrait;

class RedisCommandCollector implements CollectorInterface
{
    use ContextTrait;
    use WorkersTrait;

    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected array $buckets = ['0.001', '0.005', '0.01', '0.05', '0.1', '0.5', '1'];

    protected array $histograms = [];

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

    public function onRedisCalling(#[Event] RedisCalling $event): void
    {
        /** @var RedisCommandCollectorContext $context */
        $context = $this->getContext();
        $context->command = strtolower($event->method);
        $context->start_time = \microtime(true);
    }

    public function onRedisCalled(#[Event] RedisCalled $event): void
    {
        /** @var RedisCommandCollectorContext $context */
        $context = $this->getContext();

        $context->commands[] = [$context->command, \microtime(true) - $context->start_time];
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            /** @var RedisCommandCollectorContext $context */
            $context = $this->getContext();

            $this->task(0)->updateRequest($handler, $context->commands);
        }
    }

    public function export(): string
    {
        $histograms = $this->task(0, 1.0)->getResponse();

        return $this->formatter->histogram('app_redis_command_duration_seconds', $histograms, [], ['handler', 'command']
        );
    }
}