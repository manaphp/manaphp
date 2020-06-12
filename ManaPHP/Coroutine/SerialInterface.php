<?php

namespace ManaPHP\Coroutine;

interface SerialInterface
{
    /**
     * @param int|string $id
     *
     * @return void
     */
    public function start($id);

    /**
     * @param int|string $id
     *
     * @return void
     */
    public function stop($id);
}