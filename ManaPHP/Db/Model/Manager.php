<?php

namespace ManaPHP\Db\Model;

use ManaPHP\Component;

/**
 * Class ManaPHP\Mvc\Model\Manager
 *
 * @package modelsManager
 */
class Manager extends Component implements ManagerInterface
{
    /**
     * alias of createQuery
     *
     * @return \ManaPHP\Db\Model\QueryInterface
     * @throws \ManaPHP\Di\Exception
     * @deprecated
     */
    public function createBuilder()
    {
        return $this->createQuery();
    }

    /**
     * Creates a \ManaPHP\Db\Model\Query\Builder
     *
     * @return \ManaPHP\Db\Model\QueryInterface
     */
    public function createQuery()
    {
        return $this->_dependencyInjector->get('queryBuilder');
    }
}