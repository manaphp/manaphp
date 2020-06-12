<?php

namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use ManaPHP\Coroutine\Serial\Lock;

class Serial extends Component implements SerialInterface
{
    /**
     * @var \ManaPHP\Coroutine\Serial\Lock[]
     */
    protected $_locks = [];

    /**
     * @param int|string $id
     *
     * @return void
     */
    public function start($id)
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            if (!$lock = $this->_locks[$id] ?? false) {
                $lock = $this->_locks[$id] = new Lock();
            }

            $lock->lock();
        }
    }

    /**
     * @param int|string $id
     *
     * @return void
     */
    public function stop($id)
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            if ($lock = $this->_locks[$id] ?? false) {
                $lock->unlock();
            }
        }
    }
}