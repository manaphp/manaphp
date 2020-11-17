<?php

namespace ManaPHP\Data\Db\Model\Metadata;

interface AdapterInterface
{
    /**
     * Reads the meta-data from temporal memory
     *
     * @param string $key
     *
     * @return array|false
     */
    public function read($key);

    /**
     * Writes the meta-data to temporal memory
     *
     * @param string $key
     * @param array  $data
     *
     * @return void
     */
    public function write($key, $data);
}