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
    public static function getConnection($context = null);

    /**
     * Create a criteria for a specific model
     *
     * @param string $alias
     *
     * @return \ManaPHP\Db\Model\QueryInterface
     */
    public static function createQuery($alias = null);

    /**
     * Create a criteria for a special model
     *
     * @param string $alias
     *
     * @return \ManaPHP\Db\Model\QueryInterface
     * @deprecated
     */
    public static function query($alias = null);
}