<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Logging\LoggerInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

class Task implements TaskInterface
{
    #[Inject] protected LoggerInterface $logger;

    /**
     * @var callable
     */
    #[Value] protected $fn;
    #[Value] protected int $count = 1;

    protected Channel $channel;

    public function __construct()
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $this->channel = new Channel($this->count);

            for ($i = 0; $i < $this->count; $i++) {
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