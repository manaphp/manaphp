<?php

namespace ManaPHP\Mvc\Model;

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
     * @return \ManaPHP\Mvc\Model\QueryInterface
     * @throws \ManaPHP\Di\Exception
     * @deprecated
     */
    public function createBuilder()
    {
        return $this->createQuery();
    }

    /**
     * Creates a \ManaPHP\Mvc\Model\Query\Builder
     *
     * @return \ManaPHP\Mvc\Model\QueryInterface
     */
    public function createQuery()
    {
        return $this->_dependencyInjector->get('queryBuilder');
    }
}