<?php
namespace ManaPHP\Db\Model;

use ManaPHP\Db\Model\Criteria\Exception as CriteriaException;
use ManaPHP\Di;

class Criteria extends \ManaPHP\Model\Criteria implements CriteriaInterface
{
    /**
     * @var \ManaPHP\Db\QueryInterface
     */
    protected $_query;

    /**
     * @var string
     */
    protected $_modelName;

    /**
     * @var bool
     */
    protected $_modelReplaced = false;

    /**
     * Criteria constructor.
     *
     * @param string|array $modelName
     * @param string|array $columns
     */
    public function __construct($modelName, $columns = null)
    {
        $this->_modelName = $modelName;

        $this->_query = Di::getDefault()->get('ManaPHP\Db\Query');
        if ($columns !== null) {
            $this->_query->select($columns);
        }
    }

    /**
     * Sets SELECT DISTINCT / SELECT ALL flag
     *
     * @param bool $distinct
     *
     * @return static
     */
    public function distinct($distinct = true)
    {
        $this->_query->distinct($distinct);

        return $this;
    }

    /**
     * @param string|array $fields
     *
     * @return static
     */
    public function select($fields)
    {
        $this->_query->select($fields);

        return $this;
    }

    /**
     * @param array $expr
     *
     * @return array
     */
    public function aggregate($expr)
    {
        return $this->_replaceModelInfo()->_query->aggregate($expr);
    }

    /**
     * Appends a condition to the current conditions using a AND operator
     *
     *<code>
     *    $builder->andWhere('name = "Peter"');
     *    $builder->andWhere('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
     *</code>
     *
     * @param string|array           $filter
     * @param int|float|string|array $value
     *
     * @return static
     */
    public function where($filter, $value = [])
    {
        $this->_query->where($filter, $value);

        return $this;
    }

    /**
     * @param string $filter
     * @param array  $bind
     *
     * @return static
     */
    public function whereRaw($filter, $bind = null)
    {
        $this->_query->whereRaw($filter, $bind);

        return $this;
    }

    /**
     * Appends a BETWEEN condition to the current conditions
     *
     *<code>
     *    $builder->betweenWhere('price', 100.25, 200.50);
     *</code>
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereBetween($expr, $min, $max)
    {
        $this->_query->whereBetween($expr, $min, $max);

        return $this;
    }

    /**
     * Appends a NOT BETWEEN condition to the current conditions
     *
     *<code>
     *    $builder->notBetweenWhere('price', 100.25, 200.50);
     *</code>
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereNotBetween($expr, $min, $max)
    {
        $this->_query->whereNotBetween($expr, $min, $max);

        return $this;
    }

    /**
     * Appends an IN condition to the current conditions
     *
     *<code>
     *    $builder->inWhere('id', [1, 2, 3]);
     *</code>
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     */
    public function whereIn($expr, $values)
    {
        $this->_query->whereIn($expr, $values);

        return $this;
    }

    /**
     * Appends a NOT IN condition to the current conditions
     *
     *<code>
     *    $builder->notInWhere('id', [1, 2, 3]);
     *</code>
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     */
    public function whereNotIn($expr, $values)
    {
        $this->_query->whereNotIn($expr, $values);

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereContains($expr, $value)
    {
        $this->_query->whereContains($expr, $value);

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereStartsWith($expr, $value)
    {
        $this->_query->whereStartsWith($expr, $value);

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($expr, $value)
    {
        $this->_query->whereEndsWith($expr, $value);

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereLike($expr, $value)
    {
        $this->_query->whereLike($expr, $value);

        return $this;
    }

    /**
     * @param string $expr
     *
     * @return static
     */
    public function whereNull($expr)
    {
        $this->_query->whereNull($expr);

        return $this;
    }

    /**
     * @param string $expr
     *
     * @return static
     */
    public function whereNotNull($expr)
    {
        $this->_query->whereNotNull($expr);

        return $this;
    }

    /**
     * Sets a ORDER BY condition clause
     *
     *<code>
     *    $builder->orderBy('Robots.name');
     *    $builder->orderBy(array('1', 'Robots.name'));
     *</code>
     *
     * @param string|array $orderBy
     *
     * @return static
     */
    public function orderBy($orderBy)
    {
        $this->_query->orderBy($orderBy);

        return $this;
    }

    /**
     * Sets a LIMIT clause, optionally a offset clause
     *
     *<code>
     *    $builder->limit(100);
     *    $builder->limit(100, 20);
     *</code>
     *
     * @param int $limit
     * @param int $offset
     *
     * @return static
     */
    public function limit($limit, $offset = null)
    {
        $this->_query->limit($limit, $offset);

        return $this;
    }

    /**
     * @param int $size
     * @param int $page
     *
     * @return static
     */
    public function page($size, $page = null)
    {
        $this->_query->page($size, $page);

        return $this;
    }

    /**
     * @param int $size
     * @param int $page
     *
     * @return \ManaPHP\Paginator
     * @throws \ManaPHP\Paginator\Exception
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function paginate($size, $page = null)
    {
        return $this->_replaceModelInfo()->_query->paginate($size, $page);
    }

    /**
     * Sets a GROUP BY clause
     *
     *<code>
     *    $builder->groupBy(array('Robots.name'));
     *</code>
     *
     * @param string|array $groupBy
     *
     * @return static
     */
    public function groupBy($groupBy)
    {
        $this->_query->groupBy($groupBy);

        return $this;
    }

    /**
     * @param callable|string $indexBy
     *
     * @return static
     */
    public function indexBy($indexBy)
    {
        $this->_query->indexBy($indexBy);

        return $this;
    }

    protected function _replaceModelInfo()
    {
        if ($this->_modelReplaced) {
            return $this;
        }
        $this->_modelReplaced = true;

        /**
         * @var \ManaPHP\ModelInterface $modelName
         */
        $modelName = $this->_modelName;
        $bind = $this->_query->getBind();
        if (($db = $modelName::getDb($bind)) === false) {
            throw new CriteriaException('`:model` model db sharding for query',
                ['model' => $this->_modelName, 'context' => $bind]);
        }
        $this->_query->setDb($this->_dependencyInjector->getShared($db));

        if (($source = $modelName::getSource($bind)) === false) {
            throw new CriteriaException('`:model` model table sharding for query',
                ['model' => $this->_modelName, 'context' => $bind]);
        }
        $this->_query->from($source);

        return $this;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->_replaceModelInfo()->_query->getSql();
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getBind($key = null)
    {
        return $this->_query->getBind($key);
    }

    /**
     * Set default bind parameters
     *
     * @param array $bind
     * @param bool  $merge
     *
     * @return static
     */
    public function setBind($bind, $merge = true)
    {
        $this->_query->setBind($bind, $merge);

        return $this;
    }

    /**
     * @param array|int $options
     *
     * @return static
     */
    public function cache($options)
    {
        $this->_query->cache($options);

        return $this;
    }

    /**
     * @param string $field
     *
     * @return array
     */
    public function distinctField($field)
    {
        return $this->_replaceModelInfo()->_query->distinctField($field);
    }

    /**
     * @return array|\ManaPHP\Db\Model|false
     */
    public function fetchOne()
    {
        $r = $this->fetchAll();
        return isset($r[0]) ? $r[0] : false;
    }

    /**
     * @return array|\ManaPHP\Db\Model[]
     */
    public function fetchAll()
    {
        $rs = $this->_replaceModelInfo()->_query->execute();
        $models = [];
        foreach ($rs as $k => $result) {
            $models[$k] = new $this->_modelName($result);
        }
        return $models;
    }

    /**
     * @return array
     */
    public function asArray()
    {
        return $this->_replaceModelInfo()->_query->execute();
    }

    /**
     * @param bool $forceUseMaster
     *
     * @return static
     */
    public function forceUseMaster($forceUseMaster = true)
    {
        $this->_query->forceUseMaster($forceUseMaster);

        return $this;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->_replaceModelInfo()->_query->exists();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getSql();
    }

    /**
     * @return int
     */
    public function delete()
    {
        return $this->_replaceModelInfo()->_query->delete();
    }

    /**
     * @param $fieldValues
     *
     * @return int
     */
    public function update($fieldValues)
    {
        return $this->_replaceModelInfo()->_query->update($fieldValues);
    }
}