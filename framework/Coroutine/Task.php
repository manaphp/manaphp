<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Logging\LoggerInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

class Task extends Component implements TaskInterface
{
    #[Inject] protected LoggerInterface $logger;

    /**
     * @var callable
     */
    protected $fn;
    protected int $count;
    protected Channel $channel;

    public function __construct(callable $fn, int $count = 1)
    {
        $this->fn = $fn;
        $this->count = $count;

        if (MANAPHP_COROUTINE_ENABLED) {
            $this->channel = new Channel($count);

            for ($i = 0; $i < $count; $i++) {
                Coroutine::create([$this, 'routine']);
            }
        }
    }

    public function routine(): void
    {
        $fn = $this->fn;
        while (($data = $this->channel->pop()) !== false) {
            try {
                $fn($data);
            } catch (Throwable $throwable) {
                $this->logger->error($throwable);
            }
        }
    }

    public function push(mixed $data, int $timeout = -1): bool
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            return $this->channel->push($data, $timeout);
        } else {
            $fn = $this->fn;

            try {
                $fn($data);
            } catch (Throwable $throwable) {
                $this->logger->error($throwable);
            }
            return true;
        }
    }

    public function close(): void
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $this->channel->close();
        }
    }
}