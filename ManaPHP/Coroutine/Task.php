<?php

namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class Task extends Component implements TaskInterface
{
    /**
     * @var callable
     */
    protected $fn;

    /**
     * @var int
     */
    protected $count;

    /**
     * @var \Swoole\Coroutine\Channel
     */
    protected $channel;

    /**
     * @param callable $fn
     * @param int      $count
     */
    public function __construct($fn, $count = 1)
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

    /**
     * @return void
     */
    public function routine()
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

    /**
     * @param mixed $data
     * @param int   $timeout
     *
     * @return bool
     */
    public function push($data, $timeout = -1)
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

    /**
     * @return void
     */
    public function close()
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $this->channel->close();
        }
    }
}