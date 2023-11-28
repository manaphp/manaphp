<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Swoole\WorkersTrait;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class WorkersData implements WorkersDataInterface
{
    use WorkersTrait;

    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected ContainerInterface $container;

    public function getContext(int $cid = 0): WorkersDataContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function getWorkerRequest(string $collector, int $cid, $worker_id): void
    {
        /** @var string|WorkersCollectorInterface $collector */
        $collector = $this->container->get($collector);
        $this->sendMessage($worker_id)->getWorkerResponse($cid, $this->workers->getWorkerId(), $collector->querying());
    }

    public function getWorkerResponse(int $cid, int $worker_id, array $data): void
    {
        $context = $this->getContext($cid);
        $context->data[$worker_id] = $data;
        $context->channel->push(1);
    }

    public function get(string $collector, float $timeout = 1.0): array
    {
        /** @var string|WorkersCollectorInterface $collector */
        $collector = $this->container->get($collector);

        $context = $this->getContext();

        $context->data = [];
        unset($context->channel);

        $worker_num = $this->workers->getWorkerNum();
        $context->channel = new Channel($worker_num);
        $context->data[$this->workers->getWorkerId()] = $collector->querying();
        $context->channel->push(1);

        $my_worker_id = $this->workers->getWorkerId();
        for ($worker_id = 0; $worker_id < $worker_num; $worker_id++) {
            if ($my_worker_id !== $worker_id) {
                $this->sendMessage($worker_id)->getWorkerRequest($collector::class, Coroutine::getCid(), $my_worker_id);
            }
        }

        $end_time = microtime(true) + $timeout;
        do {
            $timeout = $end_time - microtime(true);
            if ($timeout < 0) {
                break;
            }
            $context->channel->pop($timeout);
        } while (\count($context->data) < $worker_num);

        ksort($context->data);

        return $context->data;
    }
}