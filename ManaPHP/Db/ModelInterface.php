<?php

namespace ManaPHP\Db;

/**
 * Interface ManaPHP\Mvc\ModelInterface
 *
 * @package model
 */
interface ModelInterface extends \ManaPHP\ModelInterface
{
    /**
     * @param string $alias
     *
     * @return \ManaPHP\Db\Query
     */
    public static function query($alias = null);

    /**
     * @param array|string $sql
     *
     * @return int
     */
    public static function insertBySql($sql);

    /**
     * @param array|string $sql
     *
     * @return int
     */
    public static function deleteBySql($sql);

    /**
     * @param string|array $sql
     *
     * @return int
     */
    public static function updateBySql($sql);
}