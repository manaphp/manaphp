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
     * @param mixed $context
     *
     * @return \ManaPHP\DbInterface|false
     */
    public function getConnection($context = null);

    /**
     * @param array          $fields
     * @param \ManaPHP\Model $model
     *
     * @return \ManaPHP\Db\Query
     */
    public static function query($fields = null, $model = null);

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