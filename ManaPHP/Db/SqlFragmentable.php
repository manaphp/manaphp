<?php

namespace ManaPHP\Db;

interface SqlFragmentable
{
    /**
     * @param string $name
     *
     * @return static
     */
    public function setField($name);

    /**
     *
     * @return string
     */
    public function getSql();

    /**
     * @return array
     */
    public function getBind();
}