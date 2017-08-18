<?php

namespace ManaPHP\Mvc\Model;

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
     * @return \ManaPHP\Mvc\Model\QueryInterface
     * @throws \ManaPHP\Di\Exception
     * @deprecated
     */
    public function createBuilder();

    /**
     * Creates a \ManaPHP\Mvc\Model\Query\Builder
     *
     * @return \ManaPHP\Mvc\Model\QueryInterface
     */
    public function createQuery();
}