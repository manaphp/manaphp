<?php

namespace ManaPHP\Mvc\Model\Metadata\Adapter;

use ManaPHP\Mvc\Model\Metadata;

/**
 * Class ManaPHP\Mvc\Model\Metadata\Adapter\Memory
 *
 * @package ManaPHP\Mvc\Model\Metadata\Adapter
 */
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