<?php
namespace ManaPHP\Coroutine\Context;

use ManaPHP\Component;
use ManaPHP\Exception\RuntimeException;
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
        if (!isset($this->_contexts[$fd])) {
            throw new RuntimeException('dead');
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
        if (!isset($this->_contexts[$fd])) {
            throw new RuntimeException('dead');
        }

        unset($this->_contexts[$fd]);
    }
}