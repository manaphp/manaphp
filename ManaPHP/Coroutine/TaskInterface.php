<?php

namespace ManaPHP\Coroutine;

interface TaskInterface
{
    /**
     * @param mixed $data
     * @param int   $timeout
     *
     * @return bool
     */
    public function push($data, $timeout = -1);

    /**
     * @return void
     */
    public function close();
}