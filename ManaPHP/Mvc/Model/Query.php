<?php

namespace ManaPHP\Mvc\Model;

use ManaPHP\Mvc\Model\Query\Exception as QueryException;
use ManaPHP\Utility\Text;

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
    protected $_models = [];
    protected $_modelJoins = [];

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
            $conditions = $params[0];
        } elseif (isset($params['conditions'])) {
            $conditions = $params['conditions'];
        } else {
            $conditions = $params;
            $params = [];
        }

        if (is_string($conditions)) {
            $conditions = [$conditions];
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($conditions as $k => $v) {
            if ($v === '') {
                continue;
            }

            if (is_int($k)) {
                $this->_conditions[] = Text::contains($v, ' or ', true) ? "($v)" : $v;
            } else {
                $this->_conditions[] = "[$k]=:$k";
                $this->_bind[$k] = $v;
            }
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
     * @deprecated
     */
    public function columns($columns)
    {
        return $this->select($columns);
    }

    public function from($model, $alias = null)
    {
        if ($alias === null) {
            $this->_models[] = $model;
        } else {
            $this->_models[$alias] = $model;
        }

        return $this;
    }

    public function addFrom($model, $alias = null)
    {
        return $this->from($model, $alias);
    }

    public function join($model, $condition = null, $alias = null, $type = null)
    {
        $this->_modelJoins[] = [$model, $condition, $alias, $type];

        return $this;
    }

    protected function _buildSql()
    {
        foreach ($this->_models as $alias => $model) {
            if (is_int($alias)) {
                $alias = null;
            }

            if ($model instanceof Query) {
                if ($this->_db === null) {
                    $this->_db = $model->_db;
                }
                parent::from($model, $alias);
            } else {
                /**
                 * @var \ManaPHP\Mvc\ModelInterface $modelInstance
                 */
                $modelInstance = new $model();
                if ($this->_db === null) {
                    $db = $modelInstance->getDb($this);
                    if ($db === false) {
                        throw new QueryException('`:query` query db sharding failed',
                            ['query' => get_called_class(), 'context' => $this]);
                    }
                    $this->_db = $db;
                }
                parent::from($modelInstance->getSource(), $alias);
            }
            $this->_models = [];

            foreach ($this->_modelJoins as $k => $join) {
                list($model, $condition, $alias, $type) = $join;
                if ($model instanceof Query) {
                    parent::join($model, $condition, $alias, $type);
                } else {
                    /**
                     * @var \ManaPHP\Mvc\ModelInterface $modelInstance
                     */
                    $modelInstance = new $model();

                    parent::join($modelInstance->getSource(), $condition, $alias, $type);
                }
            }
            $this->_modelJoins = [];
        }

        return parent::_buildSql();
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