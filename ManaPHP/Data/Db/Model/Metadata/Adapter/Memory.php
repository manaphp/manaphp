<?php

namespace ManaPHP\Data\Db\Model\Metadata\Adapter;

use ManaPHP\Data\Db\Model\Metadata;

/**
 * Class ManaPHP\Mvc\Model\Metadata\Adapter\Memory
 *
 * @package modelsMetadata\adapter
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