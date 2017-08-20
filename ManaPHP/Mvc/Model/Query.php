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
                    if (($db = $modelInstance->getDb($this)) === false) {
                        throw new QueryException('`:query` query db sharding failed',
                            ['query' => get_called_class(), 'context' => $this]);
                    }
                    $this->_db = $db;
                }

                if (($source = $modelInstance->getSource($this)) === false) {
                    throw new QueryException('`:query` query table sharding failed',
                        ['query' => get_called_class(), 'context' => $this]);
                }

                parent::from($source, $alias);
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

                    if (($source = $modelInstance->getSource($this)) === false) {
                        throw new QueryException('`:query` query table sharding failed',
                            ['query' => get_called_class(), 'context' => $this]);
                    }

                    parent::join($source, $condition, $alias, $type);
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