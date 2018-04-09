<?php

namespace ManaPHP\Db\Model;

/**
 * Interface ManaPHP\Mvc\Model\ManagerInterface
 *
 * @package modelsManager
 */
interface ManagerInterface
{
    /**
     * alias of createQuery
     *
     * @return \ManaPHP\Db\Model\QueryInterface
     * @deprecated
     */
    public function createBuilder();

    /**
     * Creates a \ManaPHP\Mvc\Model\Query\Builder
     *
     * @return \ManaPHP\Db\Model\QueryInterface
     */
    public function createQuery();
}