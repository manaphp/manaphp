<?php
namespace ManaPHP;

use ManaPHP\Model\NotFoundException;

/**
 * Class Query
 * @package ManaPHP
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Paginator             $paginator
 */
abstract class Query extends Component implements QueryInterface, \IteratorAggregate
{
    /**
     * @var int
     */
    protected $_limit;

    /**
     * @var int
     */
    protected $_offset;

    /**
     * @var bool
     */
    protected $_forceUseMaster = false;

    public function getIterator()
    {
        return new \ArrayIterator($this->all());
    }

    public function jsonSerialize()
    {
        return $this->all();
    }

    /**
     * @param array $fields
     *
     * @return static
     */
    public function whereRequest($fields)
    {
        foreach ($fields as $k => $v) {
            if (strpos($v, '.') === false) {
                $field = $v;
            } else {
                $parts = explode('.', $v);
                $field = $parts[1];
            }
            $value = $this->request->get(rtrim($field, '=!<>~*^@$'));
            if ($value === null) {
                continue;
            } elseif (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    continue;
                }
            } elseif (is_array($value)) {
                if (count($value) === 1 && trim($value[0]) === '') {
                    continue;
                }
            }

            $this->where($v, $value);
        }

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
        $this->_limit = $limit > 0 ? (int)$limit : null;
        $this->_offset = $offset > 0 ? (int)$offset : null;

        return $this;
    }

    /**
     * @param int $size
     * @param int $page
     *
     * @return static
     */
    public function page($size = null, $page = null)
    {
        if ($size === null) {
            $size = $this->request->get('size', 'int', 10);
        }

        if ($page === null) {
            $page = $this->request->get('page', 'int', 1);
        }

        $this->limit($size, $page ? ($page - 1) * $size : null);

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
        $this->page($size, $page);

        /** @noinspection SuspiciousAssignmentsInspection */
        $items = $this->all();

        if ($this->_limit === null) {
            $count = count($items);
        } else {
            if (count($items) % $this->_limit === 0) {
                $count = $this->count();
            } else {
                $count = $this->_offset + count($items);
            }
        }

        $this->paginator->items = $items;

        return $this->paginator->paginate($count, $this->_limit, (int)($this->_offset / $this->_limit) + 1);
    }

    /**
     * @param bool $forceUseMaster
     *
     * @return static
     */
    public function forceUseMaster($forceUseMaster = true)
    {
        $this->_forceUseMaster = $forceUseMaster;

        return $this;
    }

    /**
     * @param string|array $fields
     *
     * @return array|null
     */
    public function first($fields = null)
    {
        $r = $this->select($fields)->limit(1)->execute();
        return $r ? $r[0] : null;
    }

    /**
     * @param string|array $fields
     *
     * @return array
     */
    public function get($fields = null)
    {
        if (!$r = $this->first($fields)) {
            throw new NotFoundException('record is not exists');
        }

        return $r;
    }

    /**
     * @param string|array $fields
     *
     * @return array
     */
    public function all($fields = null)
    {
        return $this->select($fields)->execute();
    }

    /**
     * @param string $field
     * @param mixed  $default
     *
     * @return mixed
     */
    public function value($field, $default = null)
    {
        $r = $this->first([$field]);
        return isset($r[$field]) ? $r[$field] : $default;
    }
}