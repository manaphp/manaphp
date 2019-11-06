<?php
namespace ManaPHP\Merger;

use ManaPHP\Exception\NotSupportedException;

abstract class Model extends \ManaPHP\Model
{
    /**
     * @return \ManaPHP\ModelInterface
     */
    abstract public function getModel();

    /**
     * @return \ManaPHP\Merger\Query
     */
    abstract public function newQuery();

    public function getDb()
    {
        throw new NotSupportedException(__METHOD__);
    }

    public static function connection($context = null)
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function getPrimaryKey()
    {
        return $this->getModel()->getPrimaryKey();
    }

    public function getFields()
    {
        static $cached = [];

        $class = static::class;
        if (!isset($cached[$class])) {
            $fields = [];
            foreach (get_class_vars($class) as $field => $value) {
                if ($value === null && $field[0] !== '_') {
                    $fields[] = $field;
                }
            }

            $cached[$class] = $fields ?: $this->getModel()->getFields();
        }

        return $cached[$class];
    }

    public function getIntFields()
    {
        return $this->getModel()->getIntFields();
    }

    public function getNextAutoIncrementId($step = 1)
    {
        throw new NotSupportedException(__METHOD__);
    }

    /**
     * @param string $alias
     *
     * @return \ManaPHP\Merger\Query|\ManaPHP\QueryInterface
     */
    public static function query($alias = null)
    {
        $model = static::sample();
        return $model->newQuery()->setModel($model->getModel());
    }

    public function create()
    {
        throw new NotSupportedException(__METHOD__);
    }

    public static function insert($record)
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function update()
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function delete()
    {
        throw new NotSupportedException(__METHOD__);
    }

    /**
     * @param int|string|array $filters =get_object_vars(new static)
     *
     * @return \ManaPHP\Merger\Query|\ManaPHP\QueryInterface
     */
    public static function where($filters)
    {
        return static::select()->where(is_scalar($filters) ? [static::sample()->getPrimaryKey() => $filters] : $filters);
    }

    /**
     * @param array $filters =get_object_vars(new static)
     *
     * @return \ManaPHP\Merger\Query|\ManaPHP\QueryInterface
     */
    public static function search($filters)
    {
        return static::select()->search($filters);
    }
}