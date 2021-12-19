<?php
declare(strict_types=1);

namespace ManaPHP\Messaging;

use ManaPHP\Component;

abstract class AbstractQueue extends Component implements QueueInterface
{
    abstract public function do_push(string $topic, string $body, int $priority = self::PRIORITY_NORMAL): void;

    public function push(string $topic, string $body, int $priority = self::PRIORITY_NORMAL): void
    {
        $this->fireEvent('msgQueue:push', compact('topic', 'body', 'priority'));

        $this->do_push($topic, $body, $priority);
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    abstract public function do_pop(string $topic, int $timeout = PHP_INT_MAX): false|string;

    public function pop(string $topic, int $timeout = PHP_INT_MAX): false|string
    {
        if (($msg = $this->do_pop($topic, $timeout)) !== false) {
            $this->fireEvent('msgQueue:pop', compact('topic', 'msg'));
        }

        return $msg;
    }

    abstract public function do_delete(string $topic): void;

    public function delete(string $topic): void
    {
        $this->fireEvent('msgQueue:delete', compact('topic'));
        $this->do_delete($topic);
    }

    abstract public function do_length(string $topic, ?int $priority = null): int;

    public function length(string $topic, ?int $priority = null): int
    {
        return $this->do_length($topic, $priority);
    }
}