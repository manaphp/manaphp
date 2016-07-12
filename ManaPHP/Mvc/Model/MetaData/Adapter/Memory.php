<?php

namespace ManaPHP\Mvc\Model\MetaData\Adapter;

use ManaPHP\Mvc\Model\MetaData;

class Memory extends MetaData
{
    public function read($key)
    {
        return false;
    }

    public function write($key, $data)
    {

    }
}