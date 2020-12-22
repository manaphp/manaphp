<?php

namespace ManaPHP\Data\Merger;

use ManaPHP\Exception\NotSupportedException;

abstract class Model extends \ManaPHP\Data\Model
{
    /**
     * @return \ManaPHP\Data\Model
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
            $queries = $this->getInstance('ManaPHP\Data\Merger\Query', [$queries]);
        }

        return $queries->setModel($this->getModel())->select($this->getFields());
    }

    public function getDb()
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
    public function getPrimaryKey()
    {
        return $this->getModel()->getPrimaryKey();
    }

    /**
     * @return array =model_fields(new static)
     */
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

    /**
     * @return array =model_fields(new static)
     */
    public function getIntFields()
    {
        return $this->getModel()->getIntFields();
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