<?php

namespace ManaPHP\Db\Model;

use ManaPHP\Db\Model\Query\Exception as QueryException;

/**
 * Class ManaPHP\Mvc\Model\QueryBuilder
 *
 * @package queryBuilder
 *
 * @property \ManaPHP\Cache\EngineInterface   $modelsCache
 * @property \ManaPHP\Paginator               $paginator
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 */
class Query extends \ManaPHP\Db\Query implements QueryInterface
{
    protected $_models = [];
    protected $_modelJoins = [];

    /**
     * alias of select
     *
     * @param string|array $fields
     *
     * @return static
     * @deprecated
     */
    public function columns($fields)
    {
        return $this->select($fields);
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

    /**
     * @return string
     * @throws \ManaPHP\Db\Model\Query\Exception
     * @throws \ManaPHP\Db\Query\Exception
     */
    protected function _buildSql()
    {
        foreach ($this->_models as $alias => $model) {
            if (is_int($alias)) {
                $alias = null;
            }

            if ($model instanceof self) {
                if ($this->_db === null) {
                    $this->_db = $model->_db;
                }
                parent::from($model, $alias);
            } else {
                if ($this->_db === null) {
                    if (($db = $model::getDb($this)) === false) {
                        throw new QueryException(['`:query` query db sharding failed',
                            'query' => get_called_class(), 'context' => $this]);
                    }
                    $this->_db = $db;
                }

                if (($source = $model::getSource($this)) === false) {
                    throw new QueryException(['`:query` query table sharding failed',
                        'query' => get_called_class(), 'context' => $this]);
                }

                parent::from($source, $alias);
            }
            $this->_models = [];

            foreach ($this->_modelJoins as $k => $join) {
                list($model, $condition, $alias, $type) = $join;
                if ($model instanceof self) {
                    parent::join($model, $condition, $alias, $type);
                } else {
                    /**
                     * @var \ManaPHP\ModelInterface $model
                     */
                    if (($source = $model::getSource($this)) === false) {
                        throw new QueryException(['`:query` query table sharding failed',
                            'query' => get_called_class(), 'context' => $this]);
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
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function andWhere($condition, $bind = [])
    {
        return $this->where($condition, $bind);
    }
}