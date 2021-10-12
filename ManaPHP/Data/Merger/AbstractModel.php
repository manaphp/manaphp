<?php

namespace ManaPHP\Data\Merger;

use ManaPHP\Exception\NotSupportedException;

abstract class AbstractModel extends \ManaPHP\Data\AbstractModel
{
    /**
     * @return \ManaPHP\Data\ModelInterface
     */
    abstract public function getModel();

    /**
     * @return array|\ManaPHP\Data\Merger\Query
     */
    abstract public function getQueries();

    /**
     * @return \ManaPHP\Data\Merger\Query
     */
    public function newQuery()
    {
        $queries = $this->getQueries();
        if (is_array($queries)) {
            $queries = $this->getNew('ManaPHP\Data\Merger\Query', [$queries]);
        }

        return $queries->setModel($this->getModel())->select($this->fields());
    }

    public function db()
    {
        throw new NotSupportedException(__METHOD__);
    }

    /**
     * @param mixed $context
     *
     * @return void
     * @throws NotSupportedException
     */
    public static function connection(/** @noinspection PhpUnusedParameterInspection */ $context = null)
    {
        throw new NotSupportedException(__METHOD__);
    }

    /**
     * @return string =model_field(new static)
     */
    public function primaryKey()
    {
        return $this->getModel()->primaryKey();
    }

    /**
     * @return array =model_fields(new static)
     */
    public function fields()
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

            $cached[$class] = $fields ?: $this->getModel()->fields();
        }

        return $cached[$class];
    }

    /**
     * @return array =model_fields(new static)
     */
    public function intFields()
    {
        return $this->getModel()->intFields();
    }

    public function getNextAutoIncrementId($step = 1)
    {
        throw new NotSupportedException(__METHOD__);
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
}