<?php

namespace ManaPHP\Merger;

use ManaPHP\Exception\NotSupportedException;

abstract class Model extends \ManaPHP\Model
{
    /**
     * @return \ManaPHP\Model
     */
    abstract public function getModel();

    /**
     * @return array|\ManaPHP\Merger\Query
     */
    abstract public function getQueries();

    /**
     * @return \ManaPHP\Merger\Query
     */
    public function newQuery()
    {
        $queries = $this->getQueries();
        if (is_array($queries)) {
            $queries = $this->_di->get('ManaPHP\Merger\Query', [$queries]);
        }

        return $queries->setModel($this->getModel())->select($this->getFields());
    }

    public function getDb()
    {
        throw new NotSupportedException(__METHOD__);
    }

    public static function connection(/** @noinspection PhpUnusedParameterInspection */ $context = null)
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