<?php

namespace ManaPHP\Pool;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Coroutine\Channel;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var \ManaPHP\Coroutine\Channel[][]
     */
    protected $_pool = [];

    /**
     * @param object $owner
     * @param string $type
     *
     * @return static
     */
    public function remove($owner, $type = null)
    {
        $owner_id = spl_object_id($owner);

        if ($type === null) {
            unset($this->_pool[$owner_id]);
        } else {
            unset($this->_pool[$owner_id][$type]);
        }

        return $this;
    }

    /**
     * @param object $owner
     * @param int    $capacity
     * @param string $type
     *
     * @return static
     */
    public function create($owner, $capacity, $type = 'default')
    {
        $owner_id = spl_object_id($owner);

        if (isset($this->_pool[$owner_id][$type])) {
            throw new MisuseException(['`:type` pool of `:owner` is exists', 'type' => $type, 'owner' => get_class($owner)]);
        }

        $this->_pool[$owner_id][$type] = new Channel($capacity);

        return $this;
    }

    /**
     * @param object       $owner
     * @param object|array $sample
     * @param int          $size
     * @param string       $type
     *
     * @return static
     */
    public function add($owner, $sample, $size = 1, $type = 'default')
    {
        $owner_id = spl_object_id($owner);

        if (is_array($sample)) {
            if (isset($sample['class'])) {
                $class = $sample['class'];
                unset($sample['class']);
            } else {
                $class = $sample[0];
                unset($sample[0]);
            }

            $sample = $this->getInstance($class, $sample);
        } elseif (is_string($sample)) {
            $sample = $this->getInstance($sample);
        }

        if (!$queue = $this->_pool[$owner_id][$type] ?? null) {
            $this->_pool[$owner_id][$type] = $queue = new Channel($size);
        } else {
            if ($queue->length() + $size > $queue->capacity()) {
                throw new FullException(['`:type` pool of `:owner` capacity(:capacity) is not big enough',
                        'type' => $type,
                        'owner' => get_class($owner),
                        'capacity' => $queue->capacity()]);
            }
        }

        $queue->push($sample);

        for ($i = 1; $i < $size; $i++) {
            $queue->push(clone $sample);
        }

        return $this;
    }

    /**
     * @param object $owner
     * @param object $instance
     * @param string $type
     *
     * @return static
     */
    public function push($owner, $instance, $type = 'default')
    {
        if ($instance === null) {
            return $this;
        }

        $owner_id = spl_object_id($owner);

        if (!$queue = $this->_pool[$owner_id][$type] ?? null) {
            throw new MisuseException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
        }

        $queue->push($instance);

        return $this;
    }

    /**
     * @param object $owner
     * @param float  $timeout
     * @param string $type
     *
     * @return object
     */
    public function pop($owner, $timeout = null, $type = 'default')
    {
        $owner_id = spl_object_id($owner);

        if (!$queue = $this->_pool[$owner_id][$type] ?? null) {
            throw new MisuseException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
        }

        if (!$instance = $timeout ? $queue->pop($timeout) : $queue->pop()) {
            throw new BusyException(['`:type` pool of `:owner` is busy: capacity[:capacity]',
                'type' => $type,
                'capacity' => $queue->capacity(), 'owner' => get_class($owner)]);
        }

        return $instance;
    }

    /**
     * @param object $owner
     * @param string $type
     *
     * @return bool
     */
    public function isEmpty($owner, $type = 'default')
    {
        $owner_id = spl_object_id($owner);

        if (!$queue = $this->_pool[$owner_id][$type] ?? null) {
            throw new MisuseException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
        }

        return $queue->isEmpty();
    }

    /**
     * @param object $owner
     * @param string $type
     *
     * @return bool
     */
    public function exists($owner, $type = 'default')
    {
        $owner_id = spl_object_id($owner);

        return isset($this->_pool[$owner_id][$type]);
    }

    /**
     * @param object $owner
     * @param string $type
     *
     * @return int
     */
    public function size($owner, $type = 'default')
    {
        $owner_id = spl_object_id($owner);

        if (!$queue = $this->_pool[$owner_id][$type] ?? null) {
            throw new MisuseException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
        }

        return $queue->capacity();
    }
}