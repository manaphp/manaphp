<?php

namespace ManaPHP\Mvc\Model\Metadata\Adapter;

use ManaPHP\Mvc\Model\Metadata;

class Memory extends Metadata
{
    /**
     * @param string $key
     *
     * @return array|false
     */
    public function read($key)
    {
        return false;
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @return void
     */
    public function write($key, $data)
    {

    }
}