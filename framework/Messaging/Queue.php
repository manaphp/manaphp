<?php
declare(strict_types=1);

namespace ManaPHP\Messaging;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Redis\RedisBrokerInterface;

class Queue extends AbstractQueue
{
    #[Inject] protected RedisBrokerInterface $redisBroker;

    #[Value] protected string $prefix = 'cache:msgQueue:';

    protected array $priorities = [self::PRIORITY_HIGHEST, self::PRIORITY_NORMAL, self::PRIORITY_LOWEST];
    protected array $topicKeys = [];

    public function do_push(string $topic, string $body, int $priority = self::PRIORITY_NORMAL): void
    {
        if (!in_array($priority, $this->priorities, true)) {
            throw new MisuseException(['`{1}` priority of `{2}` is invalid', $priority, $topic]);
        }

        $this->redisBroker->lPush($this->prefix . $topic . ':' . $priority, $body);
    }

    public function do_pop(string $topic, int $timeout = PHP_INT_MAX): ?string
    {
        if (!isset($this->topicKeys[$topic])) {
            $keys = [];
            foreach ($this->priorities as $priority) {
                $keys[] = $this->prefix . $topic . ':' . $priority;
            }

            $this->topicKeys[$topic] = $keys;
        }


        if ($timeout === 0) {
            foreach ($this->topicKeys[$topic] as $key) {
                $r = $this->redisBroker->rPop($key);
                if ($r !== false) {
                    return $r;
                }
            }

            return null;
        } else {
            $r = $this->redisBroker->brPop($this->topicKeys[$topic], $timeout);
            return $r[1] ?? null;
        }
    }

    public function do_delete(string $topic): void
    {
        foreach ($this->priorities as $priority) {
            $this->redisBroker->del($this->prefix . $topic . ':' . $priority);
        }
    }

    public function do_length(string $topic, ?int $priority = null): int
    {
        if ($priority === null) {
            $length = 0;
            foreach ($this->priorities as $p) {
                $length += $this->redisBroker->lLen($this->prefix . $topic . ':' . $p);
            }

            return $length;
        } else {
            return $this->redisBroker->lLen($this->prefix . $topic . ':' . $priority);
        }
    }
}
