<?php
declare(strict_types=1);

namespace ManaPHP\Pool;

use ManaPHP\Component;
use ManaPHP\Coroutine\Channel;
use ManaPHP\Exception\MisuseException;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var \ManaPHP\Coroutine\Channel[][]
     */
    protected array $pool = [];

    public function remove(object $owner, ?string $type = null): static
    {
        $owner_id = get_class($owner) . ':' . spl_object_id($owner);

        if ($type === null) {
            unset($this->pool[$owner_id]);
        } else {
            unset($this->pool[$owner_id][$type]);
        }

        return $this;
    }

    public function create(object $owner, int $capacity, string $type = 'default'): static
    {
        $owner_id = get_class($owner) . ':' . spl_object_id($owner);

        if (isset($this->pool[$owner_id][$type])) {
            throw new MisuseException(['`%s` pool of `%s` is exists', $type, get_class($owner)]);
        }

        $this->pool[$owner_id][$type] = new Channel($capacity);

        return $this;
    }

    public function add(object $owner, object $sample, int $size = 1, string $type = 'default'): static
    {
        $owner_id = get_class($owner) . ':' . spl_object_id($owner);

        if (!$queue = $this->pool[$owner_id][$type] ?? null) {
            $this->pool[$owner_id][$type] = $queue = new Channel($size);
        } else {
            if ($queue->length() + $size > $queue->capacity()) {
                throw new FullException(
                    ['`%s` pool of `%s` capacity(%d) is not big enough', $type, get_class($owner), $queue->capacity()]
                );
            }
        }

        $queue->push($sample);

        for ($i = 1; $i < $size; $i++) {
            $queue->push(clone $sample);
        }

        return $this;
    }

    public function push(object $owner, object $instance, string $type = 'default'): static
    {
        $owner_id = get_class($owner) . ':' . spl_object_id($owner);

        if (!$queue = $this->pool[$owner_id][$type] ?? null) {
            throw new MisuseException(['`%s` pool of `%s` is not exists', $type, get_class($owner)]);
        }

        $queue->push($instance);

        $this->fireEvent('poolManager:push', compact('owner', 'instance', 'type'));

        return $this;
    }

    public function pop(object $owner, ?float $timeout = null, string $type = 'default'): object
    {
        $owner_id = get_class($owner) . ':' . spl_object_id($owner);

        if (!$queue = $this->pool[$owner_id][$type] ?? null) {
            throw new MisuseException(['`%s` pool of `%s` is not exists', $type, get_class($owner)]);
        }

        $this->fireEvent('poolManager:popping', compact('owner', 'type'));

        if (!$instance = $timeout ? $queue->pop($timeout) : $queue->pop()) {
            $this->fireEvent('poolManager:popped', compact('owner', 'type', 'instance'));
            $capacity = $queue->capacity();
            throw new BusyException(['`%s` pool of `%s` is busy: capacity[%d]', $type, get_class($owner), $capacity]);
        }

        $this->fireEvent('poolManager:popped', compact('owner', 'type', 'instance'));

        return $instance;
    }

    public function get(object $owner, ?float $timeout = null, string $type = 'default'): Proxy
    {
        $instance = $this->pop($owner, $timeout, $type);

        return new Proxy($this, $owner, $instance, $type);
    }

    public function transient(Transientable $owner, ?float $timeout = null, string $type = 'default'): Transient
    {
        $instance = $this->pop($owner, $timeout, $type);

        return new Transient($this, $owner, $instance, $type);
    }

    public function isEmpty(object $owner, string $type = 'default'): bool
    {
        $owner_id = get_class($owner) . ':' . spl_object_id($owner);

        if (!$queue = $this->pool[$owner_id][$type] ?? null) {
            throw new MisuseException(['`%s` pool of `%s` is not exists', $type, get_class($owner)]);
        }

        return $queue->isEmpty();
    }

    public function exists(object $owner, string $type = 'default'): bool
    {
        $owner_id = get_class($owner) . ':' . spl_object_id($owner);

        return isset($this->pool[$owner_id][$type]);
    }

    public function size(object $owner, string $type = 'default'): int
    {
        $owner_id = get_class($owner) . ':' . spl_object_id($owner);

        if (!$queue = $this->pool[$owner_id][$type] ?? null) {
            throw new MisuseException(['`%s` pool of `%s` is not exists', $type, get_class($owner)]);
        }

        return $queue->capacity();
    }
}