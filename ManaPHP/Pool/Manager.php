<?php
namespace ManaPHP\Pool;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use Swoole\Coroutine\Channel;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var \Swoole\Coroutine\Channel[][]
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

        if (!isset($this->_pool[$owner_id])) {
            return $this;
        }

        if (MANAPHP_COROUTINE_ENABLED) {
            foreach ($type === null ? array_keys($this->_pool[$owner_id]) : [$type] as $current) {
                $queue = $this->_pool[$owner_id][$current];

                while (!$queue->isEmpty()) {
                    $queue->pop();
                }

                unset($this->_pool[$owner_id][$current]);
            }

            if ($type === null || count($this->_pool[$owner_id]) === 0) {
                unset($this->_pool[$owner_id]);
            }
        } else {
            if ($type === null) {
                unset($this->_pool[$owner_id]);
            } else {
                unset($this->_pool[$owner_id][$type]);
            }
        }

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

            $sample = $this->_di->get($class, $sample);
        } elseif (is_string($sample)) {
            $sample = $this->_di->get($sample);
        }

        if (MANAPHP_COROUTINE_ENABLED) {
            if (isset($this->_pool[$owner_id][$type])) {
                throw new MisuseException(['`:type` pool of `:owner` is exists', 'type' => $type, 'owner' => get_class($owner)]);
            }

            $this->_pool[$owner_id][$type] = $queue = new Channel($size);

            $queue->push($sample);

            for ($i = 1; $i < $size; $i++) {
                $queue->push(clone $sample);
            }
        } else {
            if (isset($this->_pool[$owner_id]) && array_key_exists($type, $this->_pool[$owner_id])) {
                throw new MisuseException(['`:type` pool of `:owner` is exists', 'type' => $type, 'owner' => get_class($owner)]);
            }

            $this->_pool[$owner_id][$type] = $sample;
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

        if (MANAPHP_COROUTINE_ENABLED) {
            if (!$queue = $this->_pool[$owner_id][$type] ?? null) {
                throw new MisuseException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
            }

            $queue->push($instance);
        } else {
            if (!isset($this->_pool[$owner_id]) || !array_key_exists($type, $this->_pool[$owner_id])) {
                throw new MisuseException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
            }

            $this->_pool[$owner_id][$type] = $instance;
        }

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

        if (MANAPHP_COROUTINE_ENABLED) {
            if (!$queue = $this->_pool[$owner_id][$type] ?? null) {
                throw new MisuseException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
            }

            /** @noinspection PhpMethodParametersCountMismatchInspection */
            if (!$instance = $timeout ? $queue->pop($timeout) : $queue->pop()) {
                throw new BusyException(['`:type` pool of `:owner` is busy', 'type' => $type, 'owner' => get_class($owner)]);
            }

            return $instance;
        } else {
            if (!$instance = $this->_pool[$owner_id][$type] ?? null) {
                throw new BusyException(['`:type` pool of `:owner` is busy', 'type' => $type, 'owner' => get_class($owner)]);
            }
            $this->_pool[$owner_id][$type] = null;

            return $instance;
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

        if (MANAPHP_COROUTINE_ENABLED) {
            if ($queue = $this->_pool[$owner_id][$type] ?? null) {
                throw new MisuseException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
            }
            return $queue->isEmpty();
        } else {
            if (!isset($this->_pool[$owner_id]) || !array_key_exists($type, $this->_pool[$owner_id])) {
                throw new MisuseException(['`:type` pool of `:owner` is not exists', 'type' => $type, 'owner' => get_class($owner)]);
            }

            return $this->_pool[$owner_id][$type] === null;
        }
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

        if (MANAPHP_COROUTINE_ENABLED) {
            return isset($this->_pool[$owner_id][$type]);
        } else {
            return isset($this->_pool[$owner_id]) && array_key_exists($type, $this->_pool[$owner_id]);
        }
    }
}