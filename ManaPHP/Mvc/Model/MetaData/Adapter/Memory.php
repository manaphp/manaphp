<?php

namespace ManaPHP\Mvc\Model\MetaData\Adapter;

use ManaPHP\Mvc\Model\MetaData;

class Memory extends MetaData
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