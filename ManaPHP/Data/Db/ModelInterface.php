<?php

namespace ManaPHP\Data\Db;

interface ModelInterface extends \ManaPHP\Data\ModelInterface
{
    /**
     * @param string $alias
     *
     * @return \ManaPHP\Data\Db\Query
     */
    public static function query($alias = null);

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public static function insertBySql($sql, $bind = []);

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public static function deleteBySql($sql, $bind = []);

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     */
    public static function updateBySql($sql, $bind = []);
}