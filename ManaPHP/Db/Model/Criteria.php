<?php
namespace ManaPHP\Db\Model;

use ManaPHP\Di;
use ManaPHP\Exception\NotSupportedException;

/**
 * Class ManaPHP\Db\Model\Criteria
 *
 * @package ManaPHP\Db\Model
 * @property \ManaPHP\Db\Model               $_model
 * @property \ManaPHP\Model\Relation\Manager $relationsManager
 */
class Criteria extends \ManaPHP\Model\Criteria implements CriteriaInterface
{
    /**
     * @var \ManaPHP\Db\QueryInterface
     */
    protected $_query;

    /**
     * @var bool
     */
    protected $_modelReplaced = false;

    /**
     * @var array
     */
    protected $_columns;

    /**
     * Criteria constructor.
     *
     * @param string|\ManaPHP\Db\Model $model
     * @param array                    $fields
     */
    public function __construct($model, $fields = null)
    {
        $this->_model = is_string($model) ? new $model : $model;
        $this->_di = Di::getDefault();

        $this->_query = $this->_di->get('ManaPHP\Db\Query');
        if ($fields !== null) {
            $this->_columns = $fields;
            $this->_query->select($fields);
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
     * @param array $fields
     *
     * @return static
     */
    public function select($fields)
    {
        $this->_query->select($fields);
        $this->_columns = $fields;

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
    public function where($filter, $value = null)
    {
        if (is_string($filter) && strpos($filter, '@=')) {
            $field = substr($filter, 0, -2);
            $times = $this->_normalizeTimeBetween($field, $value);
            $this->_query->whereBetween($field, $times[0], $times[1]);
        } elseif (is_array($filter)) {
            $query = $this->_query;
            foreach ((array)$filter as $k => $v) {
                if (strpos($k, '@=')) {
                    $field = substr($filter, 0, -2);
                    $times = $this->_normalizeTimeBetween($field, $v);
                    $query->whereBetween($field, $times[0], $times[1]);
                } else {
                    $query->where($k, $v);
                }
            }
        } else {
            $this->_query->where($filter, $value);
        }

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
     * @param string           $field
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereBetween($field, $min, $max)
    {
        $this->_query->whereBetween($field, $min, $max);

        return $this;
    }

    /**
     * Appends a NOT BETWEEN condition to the current conditions
     *
     *<code>
     *    $builder->notBetweenWhere('price', 100.25, 200.50);
     *</code>
     *
     * @param string           $field
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     */
    public function whereNotBetween($field, $min, $max)
    {
        $this->_query->whereNotBetween($field, $min, $max);

        return $this;
    }

    /**
     * Appends an IN condition to the current conditions
     *
     *<code>
     *    $builder->inWhere('id', [1, 2, 3]);
     *</code>
     *
     * @param string                           $field
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     */
    public function whereIn($field, $values)
    {
        $this->_query->whereIn($field, $values);

        return $this;
    }

    /**
     * Appends a NOT IN condition to the current conditions
     *
     *<code>
     *    $builder->notInWhere('id', [1, 2, 3]);
     *</code>
     *
     * @param string                           $field
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     */
    public function whereNotIn($field, $values)
    {
        $this->_query->whereNotIn($field, $values);

        return $this;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return static
     */
    public function whereInset($field, $value)
    {
        $this->_query->whereInset($field, $value);

        return $this;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return static
     */
    public function whereNotInset($field, $value)
    {
        $this->_query->whereNotInset($field, $value);

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereContains($field, $value)
    {
        $this->_query->whereContains($field, $value);

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereNotContains($field, $value)
    {
        $this->_query->whereNotContains($field, $value);

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereStartsWith($field, $value, $length = null)
    {
        $this->_query->whereStartsWith($field, $value, $length);

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     * @param int          $length
     *
     * @return static
     */
    public function whereNotStartsWith($field, $value, $length = null)
    {
        $this->_query->whereNotStartsWith($field, $value, $length);

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereEndsWith($field, $value)
    {
        $this->_query->whereEndsWith($field, $value);

        return $this;
    }

    /**
     * @param string|array $field
     * @param string       $value
     *
     * @return static
     */
    public function whereNotEndsWith($field, $value)
    {
        $this->_query->whereNotEndsWith($field, $value);

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
     * @param string|array $expr
     * @param string       $value
     *
     * @return static
     */
    public function whereNotLike($expr, $value)
    {
        $this->_query->whereNotLike($expr, $value);

        return $this;
    }

    /**
     * @param string $field
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereRegex($field, $regex, $flags = '')
    {
        $this->_query->whereRegex($field, $regex, $flags);

        return $this;
    }

    /**
     * @param string $field
     * @param string $regex
     * @param string $flags
     *
     * @return static
     */
    public function whereNotRegex($field, $regex, $flags = '')
    {
        $this->_query->whereNotRegex($field, $regex, $flags);

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
     * @return \ManaPHP\Paginator
     */
    public function paginate($size = null, $page = null)
    {
        $paginator = $this->_replaceModelInfo()->_query->paginate($size, $page);

        foreach ($this->_with as $k => $v) {
            $name = is_string($k) ? $k : $v;
            $relation = $this->relationsManager->get($this->_model, $name);
            foreach ($paginator->items as &$item) {
                if (is_int($k)) {
                    $data = $relation->criteria($item)->fetch();
                } else {
                    if (is_string($v) || is_array($v)) {
                        $data = $relation->criteria($item)->select($v)->fetch();
                    } elseif (is_callable($v)) {
                        $data = $v($relation->criteria($item));
                    } else {
                        throw new NotSupportedException(['`:with` with is invalid', 'with' => $k]);
                    }
                }

                if ($data instanceof Criteria) {
                    $data = $data->fetch();
                }

                if (is_array($data) && isset($data[0])) {
                    foreach ($data as $kk => $vv) {
                        $data[$kk] = $vv->toArray();
                    }
                } elseif (is_object($data)) {
                    $data = $data->toArray();
                }

                $item[$name] = $data;
            }
        }

        return $paginator;
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
     * @param callable|string|array $indexBy
     *
     * @return static
     */
    public function indexBy($indexBy)
    {
        $this->_index = $indexBy;

        $this->_query->indexBy($indexBy);

        return $this;
    }

    /**
     * @return $this
     */
    protected function _replaceModelInfo()
    {
        if ($this->_modelReplaced) {
            return $this;
        }
        $this->_modelReplaced = true;

        $bind = $this->_query->getBind();
        $db = $this->_model->getDb($bind);
        $this->_query->setDb($this->_di->getShared($db));

        $source = $this->_model->getSource($bind);
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
    public function values($field)
    {
        return $this->_replaceModelInfo()->_query->values($field);
    }

    /**
     * @return array
     */
    public function execute()
    {
        if ($this->_columns === null) {
            $this->select($this->_model->getFields());
        }
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
        try {
            return $this->getSql();
        } catch (\Exception $e) {
            return '';
        }
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