<?php
declare(strict_types=1);

namespace ManaPHP\Messaging;

use ManaPHP\Exception\MisuseException;

/**
 * @property-read \ManaPHP\Data\RedisBrokerInterface $redisBroker
 */
class Queue extends AbstractQueue
{
    protected string $prefix;

    protected array $priorities = [self::PRIORITY_HIGHEST, self::PRIORITY_NORMAL, self::PRIORITY_LOWEST];
    protected array $topicKeys = [];

    public function __construct(string $prefix = 'cache:msgQueue:')
    {
        $this->prefix = $prefix;
    }

    public function do_push(string $topic, string $body, int $priority = self::PRIORITY_NORMAL): void
    {
        if (!in_array($priority, $this->priorities, true)) {
            throw new MisuseException(['`%d` priority of `%s` is invalid', $priority, $topic]);
        }

        $this->redisBroker->lPush($this->prefix . $topic . ':' . $priority, $body);
    }

    public function do_pop(string $topic, int $timeout = PHP_INT_MAX): false|string
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

            return false;
        } else {
            $r = $this->redisBroker->brPop($this->topicKeys[$topic], $timeout);
            return $r[1] ?? false;
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
