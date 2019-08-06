<?php
namespace ManaPHP\Coroutine\Context;

use ManaPHP\Component;
use Swoole\Coroutine;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var array
     */
    protected $_contexts = [];

    /**
     * @param int $fd
     *
     * @return \ArrayObject
     */
    public function save($fd)
    {
        $context = Coroutine::getContext();

        foreach ($context as $k => $v) {
            if (!$v instanceof Stickyable) {
                unset($context[$k]);
            }
        }

        return $this->_contexts[$fd] = $context;
    }

    /**
     * @param int $fd
     *
     * @return \ArrayObject
     */
    public function restore($fd)
    {
        while (!isset($this->_contexts[$fd])) {
            Coroutine::sleep(0.001);
        }

        /** @var \ArrayObject $context */
        $context = Coroutine::getContext();

        foreach ($this->_contexts[$fd] as $k => $v) {
            $context[$k] = $v;
        }

        return $context;
    }

    /**
     * @param int $fd
     *
     * @return void
     */
    public function delete($fd)
    {
        unset($this->_contexts[$fd]);
    }
}