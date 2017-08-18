<?php

namespace ManaPHP\Mvc\Model;

/**
 * Class ManaPHP\Mvc\Model\QueryBuilder
 *
 * @package queryBuilder
 *
 * @property \ManaPHP\Cache\AdapterInterface     $modelsCache
 * @property \ManaPHP\Paginator                  $paginator
 * @property \ManaPHP\Mvc\Model\ManagerInterface $modelsManager
 * @property \ManaPHP\Mvc\DispatcherInterface    $dispatcher
 */
class Query extends \ManaPHP\Db\Query implements QueryInterface
{
    /**
     * @param mixed $params
     *
     * @return static
     */
    public function buildFromArray($params)
    {
        if ($params === null) {
            $params = [];
        } elseif (is_string($params)) {
            $params = [$params];
        }

        if (isset($params[0])) {
            $this->_conditions = $params[0];
        } elseif (isset($params['conditions'])) {
            $this->_conditions = $params['conditions'];
        } else {
            $this->_conditions = $params;
            $params = [];
        }

        if (isset($params[1])) {
            $params['bind'] = $params[1];
            unset($params[1]);
        }

        if (isset($params['bind'])) {
            $this->_bind = array_merge($this->_bind, $params['bind']);
        }

        if (isset($params['distinct'])) {
            $this->distinct($params['distinct']);
        }

        if (isset($params['columns'])) {
            $this->select($params['columns']);
        }

        if (isset($params['order'])) {
            $this->orderBy($params['order']);
        }

        if (isset($params['limit'])) {
            $this->limit($params['limit'], isset($params['offset']) ? $params['offset'] : 0);
        }

        if (isset($params['offset'])) {
            $this->_offset = (int)$params['offset'];
        }

        if (isset($params['for_update'])) {
            $this->forUpdate($params['for_update']);
        }

        return $this;
    }

    /**
     * alias of select
     *
     * @param string|array $columns
     *
     * @return static
     */
    public function columns($columns)
    {
        return $this->select($columns);
    }

    public function from($model, $alias = null)
    {
        if ($model instanceof Query) {
            if ($this->_db === null) {
                $this->_db = $model->_db;
            }
            return parent::from($model, $alias);
        } else {
            /**
             * @var \ManaPHP\Mvc\ModelInterface $modelInstance
             */
            $modelInstance = new $model();
            if ($this->_db === null) {
                $this->_db = $modelInstance->getReadConnection();
            }

            return parent::from($modelInstance->getSource(), $alias);
        }
    }

    public function addFrom($model, $alias = null)
    {
        return $this->from($model, $alias);
    }

    public function join($model, $condition = null, $alias = null, $type = null)
    {

        if ($model instanceof Query) {
            return parent::join($model, $condition, $alias, $type);
        } else {
            /**
             * @var \ManaPHP\Mvc\ModelInterface $modelInstance
             */
            $modelInstance = new $model();

            return parent::join($modelInstance->getSource(), $condition, $alias, $type);
        }
    }

    /**
     * alias of where
     *
     * @param string|array           $condition
     * @param int|float|string|array $bind
     *
     * @deprecated
     *
     * @return static
     */
    public function andWhere($condition, $bind = [])
    {
        return $this->where($condition, $bind);
    }
}