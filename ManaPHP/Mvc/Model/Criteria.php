<?php
namespace ManaPHP\Mvc\Model;

use ManaPHP\Component;
use ManaPHP\Di;
use ManaPHP\Mvc\Model\Criteria\Exception as CriteriaException;
use ManaPHP\Utility\Text;

class Criteria extends Component implements CriteriaInterface
{
    /**
     * @var \ManaPHP\Db\QueryInterface
     */
    protected $_query;

    /**
     * @var array
     */
    protected $_aggregate;

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
     * @param string       $modelName
     * @param string|array $columns
     */
    public function __construct($modelName, $columns = null)
    {
        $this->_modelName = $modelName;

        $this->_query = Di::getDefault()->get('ManaPHP\Db\Query');
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
     * @param string|array $columns
     *
     * @return static
     */
    public function select($columns)
    {
        $this->_query->select($columns);

        return $this;
    }

    /**
     * @param array $expr
     *
     * @return static
     */
    public function aggregate($expr)
    {
        $this->_aggregate = $expr;

        $this->_query->aggregate($expr);

        return $this;
    }

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
                $this->where(Text::contains($v, ' or ', true) ? "($v)" : $v);
            } else {
                $this->where($k, $v);
            }
        }

        if (isset($params[1])) {
            $params['bind'] = $params[1];
            unset($params[1]);
        }

        if (isset($params['bind'])) {
            $this->setBind($params['bind']);
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

        if (isset($params['for_update'])) {
            $this->_query->forUpdate($params['for_update']);
        }

        return $this;
    }

    /**
     * Appends a condition to the current conditions using a AND operator
     *
     *<code>
     *    $builder->andWhere('name = "Peter"');
     *    $builder->andWhere('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
     *</code>
     *
     * @param string|array           $condition
     * @param int|float|string|array $bind
     *
     * @return static
     */
    public function where($condition, $bind = [])
    {
        $this->_query->where($condition, $bind);

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
    public function betweenWhere($expr, $min, $max)
    {
        $this->_query->betweenWhere($expr, $min, $max);

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
    public function notBetweenWhere($expr, $min, $max)
    {
        $this->_query->notBetweenWhere($expr, $min, $max);

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
    public function inWhere($expr, $values)
    {
        $this->_query->inWhere($expr, $values);

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
    public function notInWhere($expr, $values)
    {
        $this->_query->notInWhere($expr, $values);

        return $this;
    }

    /**
     * @param string|array $expr
     * @param string       $like
     *
     * @return static
     */
    public function likeWhere($expr, $like)
    {
        $this->_query->likeWhere($expr, $like);

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
    public function limit($limit, $offset = 0)
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
    public function page($size, $page = 1)
    {
        $this->_query->page($size, $page);

        return $this;
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

        $modelName = $this->_modelName;
        $bind = $this->_query->getBind();
        /** @noinspection PhpUndefinedMethodInspection */
        if (($db = $modelName::getDb($bind)) === false) {
            throw new CriteriaException('`:model` model db sharding for query',
                ['model' => $this->_modelName, 'context' => $bind]);
        }
        $this->_query->setDb($this->_dependencyInjector->getShared($db));

        /** @noinspection PhpUndefinedMethodInspection */
        if (($source = $modelName::getSource($bind)) === false) {
            throw new CriteriaException('`:model` model table sharding for query',
                ['model' => $this->_modelName, 'context' => $bind]);
        }
        $this->_query->from($source);

        return $this;
    }

    /**
     * @return string
     * @throws \ManaPHP\Mvc\Model\Criteria\Exception
     * @throws \ManaPHP\Db\Query\Exception
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
     *
     * @return array
     * @throws \ManaPHP\Mvc\Model\Criteria\Exception
     * @throws \ManaPHP\Db\Query\Exception
     */
    public function execute()
    {
        return $this->_replaceModelInfo()->_query->execute();
    }

    public function exists()
    {
        return $this->_replaceModelInfo()->_query->exists();
    }
}