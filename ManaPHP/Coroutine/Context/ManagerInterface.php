<?php
namespace ManaPHP\Coroutine\Context;

interface ManagerInterface
{
    /**
     * @param int $fd
     *
     * @return \ArrayObject
     */
    public function save($fd);

    /**
     * @param int $fd
     *
     * @return \ArrayObject
     */
    public function restore($fd);

    /**
     * @param int $fd
     *
     * @return void
     */
    public function delete($fd);
}