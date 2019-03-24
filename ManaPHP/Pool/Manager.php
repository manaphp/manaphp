<?php
namespace ManaPHP\Pool;

use ManaPHP\Component;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var \SplQueue[][]
     */
    protected $_pool = [];

    /**
     * @param object $owner
     * @param string $type
     *
     * @return static
     */
    public function remove($owner, $type = 'default')
    {
        $owner_id = spl_object_id($owner);

        if (!isset($this->_pool[$owner_id])) {
            return $this;
        }

        foreach ($type === null ? array_keys($this->_pool[$owner_id]) : [$type] as $current) {
            $queue = $this->_pool[$owner_id][$current];

            while (!$queue->isEmpty()) {
                $instance = $queue->pop();
                unset($instance);
            }

            unset($this->_pool[$owner_id][$current]);
        }

        if (count($this->_pool[$owner_id]) === 0) {
            unset($this->_pool[$owner_id]);
        }

        return $this;
    }

    /**
     * @param object $owner
     * @param object $sample
     * @param int    $size
     * @param string $type
     *
     * @return static
     */
    public function add($owner, $sample, $size = 1, $type = 'default')
    {
        $size = defined('MANAPHP_CO') ? $size : 1;

        $owner_id = spl_object_id($owner);

        if (!isset($this->_pool[$owner_id][$type])) {
            $this->_pool[$owner_id][$type] = $queue = new \SplQueue();
        } else {
            $queue = $this->_pool[$owner_id][$type];
        }

        $queue->push(clone $sample);

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

        if (!isset($this->_pool[$owner_id][$type])) {
            $queue = new \SplQueue();
            $this->_pool[$owner_id][$type] = $queue;
        } else {
            $queue = $this->_pool[$owner_id][$type];
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
     *
     * @throws \ManaPHP\Pool\NotExistsException
     */
    public function pop($owner, $timeout = null, $type = 'default')
    {
        $owner_id = spl_object_id($owner);

        if (!isset($this->_pool[$owner_id][$type])) {
            throw new NotExistsException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
        }

        $queue = $this->_pool[$owner_id][$type];

        if (!$queue->isEmpty()) {
            return $queue->pop();
        }

        if (!$timeout) {
            return null;
        }

        $end_time = microtime(true) + $timeout;
        do {
            if (!$queue->isEmpty()) {
                return $queue->pop();
            }
            usleep(1000);
        } while ($end_time > microtime(true));

        if ($queue->isEmpty()) {
            throw new BusyException(['`:type` pool of `:owner` is busy', 'type' => $type, 'owner' => get_class($owner)]);
        } else {
            return $queue->pop();
        }
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

        if (!isset($this->_pool[$owner_id][$type])) {
            throw new NotExistsException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
        }

        $queue = $this->_pool[$owner_id][$type];

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
}