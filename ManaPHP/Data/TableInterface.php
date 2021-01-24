<?php

namespace ManaPHP\Data;

interface TableInterface
{
    /**
     * Returns table name mapped in the model
     *
     * @return string
     */
    public function table();

    /**
     * Gets internal database connection
     *
     * @return string
     */
    public function db();

    /**
     * @return array
     */
    public function getAnyShard();

    /**
     * @param array|\ManaPHP\Data\Model $context
     *
     * @return array
     */
    public function getUniqueShard($context);

    /**
     * @param array|\ManaPHP\Data\Model $context
     *
     * @return array
     */
    public function getMultipleShards($context);

    /**
     * @return array
     */
    public function getAllShards();

    /**
     * @return static
     */
    public static function sample();
}