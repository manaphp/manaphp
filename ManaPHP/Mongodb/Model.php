<?php

namespace ManaPHP\Mongodb;

use ManaPHP\Di;
use ManaPHP\Exception\InvalidFormatException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\RuntimeException;
use MongoDB\BSON\ObjectID;

/**
 * Class ManaPHP\Mongodb\Model
 *
 * @package model
 *
 */
class Model extends \ManaPHP\Model
{
    /**
     * @var \MongoDB\BSON\ObjectId
     */
    public $_id;

    /**
     * Gets the connection used to crud data to the model
     *
     * @param mixed $context
     *
     * @return string|false
     */
    public function getDb($context = null)
    {
        return 'mongodb';
    }

    /**
     * @param mixed $context
     *
     * @return \ManaPHP\MongodbInterface
     */
    public function getConnection($context = null)
    {
        $db = $this->getDb($context);
        return $this->_di->getShared($db);
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            $fields = $this->getFields();

            if (in_array('id', $fields, true)) {
                return $cached[$calledClass] = 'id';
            }

            $source = $this->getSource();
            $pos = strrpos($source, '_');
            $tryField = ($pos === false ? $source : substr($source, $pos + 1)) . '_id';
            if (in_array($tryField, $fields, true)) {
                return $cached[$calledClass] = $tryField;
            }

            return $cached[$calledClass] = '_id';
        }

        return $cached[$calledClass];
    }

    /**
     * @return null|string
     */
    public function getAutoIncrementField()
    {
        $primaryKey = $this->getPrimaryKey();

        return $primaryKey !== '_id' ? $primaryKey : null;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            return $cached[$calledClass] = array_keys($this->getFieldTypes());
        }

        return $cached[$calledClass];
    }

    /**
     * @return array
     */
    public function getIntTypeFields()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            $fields = [];
            foreach ($this->getFieldTypes() as $field => $type) {
                if ($type === 'integer') {
                    $fields[] = $field;
                }
            }

            return $cached[$calledClass] = $fields;
        }

        return $cached[$calledClass];
    }

    /**
     * boolean, integer, double, string, array, objectid
     *
     * @return array
     */
    public function getFieldTypes()
    {
        static $cached = [];

        $calledClass = get_called_class();

        if (!isset($cached[$calledClass])) {
            $fieldTypes = [];
            $rc = new \ReflectionClass(get_called_class());

            foreach ($rc->getProperties(\ReflectionProperty::IS_PUBLIC) as $rp) {
                if ($rp->isStatic()) {
                    continue;
                }

                $phpdoc = $rp->getDocComment();
                if (!$phpdoc) {
                    throw new RuntimeException(['`:property` property does not contain phpdoc', 'property' => $rp->getName()]);
                }

                if (!preg_match('#@var\s+(\S+)#', $phpdoc, $match)) {
                    throw new InvalidFormatException([
                        '`:property` property phpdoc does not contain data type definition: `:phpdoc`',
                        'property' => $rp->getName(),
                        'phpdoc' => $phpdoc
                    ]);
                }

                switch ($match[1]) {
                    case 'string':
                        $type = 'string';
                        break;
                    case 'int':
                    case 'integer':
                        $type = 'integer';
                        break;
                    case 'float':
                    case 'double':
                        $type = 'double';
                        break;
                    case 'bool':
                    case 'boolean':
                        $type = 'boolean';
                        break;
                    case 'array':
                    case '[]':
                        $type = 'array';
                        break;
                    case '\MongoDB\BSON\ObjectId':
                    case 'ObjectId':
                        $type = 'objectid';
                        break;
                    default:
                        throw new InvalidValueException(['`:property` property `:type` type unsupported', 'property' => $rp->getName(), 'type' => $match[1]]);
                        break;
                }

                $fieldTypes[$rp->getName()] = $type;
            }

            return $cached[$calledClass] = $fieldTypes;
        }

        return $cached[$calledClass];
    }

    /**
     * @return bool
     */
    protected function _createAutoIncrementIndex()
    {
        $autoIncField = $this->getAutoIncrementField();

        $command = [
            'createIndexes' => $this->getSource(),
            'indexes' => [
                [
                    'key' => [
                        $autoIncField => 1
                    ],
                    'unique' => true,
                    'name' => $autoIncField
                ]
            ]
        ];

        $this->getConnection()->command($command);

        return true;
    }

    /**
     * @param int $step
     * @param     int
     *
     * @return int
     */
    public function generateAutoIncrementId($step = 1)
    {
        $command = [
            'findAndModify' => 'auto_increment_id',
            'query' => ['_id' => $this->getSource()],
            'update' => ['$inc' => ['current_id' => $step]],
            'new' => true,
            'upsert' => true
        ];

        $r = $this->getConnection()->command($command);
        $r->setTypeMap(['root' => 'array', 'document' => 'array']);
        $r = $r->toArray();
        $id = $r[0]['value']['current_id'];

        if ($id === $step) {
            $this->_createAutoIncrementIndex();
        }

        return $id;
    }

    /**
     * @param string $type
     * @param mixed  $value
     *
     * @return bool|float|int|string|array|\MongoDB\BSON\ObjectID|\MongoDB\BSON\UTCDateTime
     */
    public function getNormalizedValue($type, $value)
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'string') {
            return is_string($value) ? $value : (string)$value;
        } elseif ($type === 'integer') {
            return is_int($value) ? $value : (int)$value;
        } elseif ($type === 'double') {
            return is_float($value) ? $value : (double)$value;
        } elseif ($type === 'objectid') {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return is_scalar($type) ? new ObjectID($value) : $value;
        } elseif ($type === 'boolean') {
            return is_bool($value) ? $value : (bool)$value;
        } elseif ($type === 'array') {
            return (array)$value;
        } else {
            throw new InvalidValueException(['unsupported `:type` type', 'type' => $type]);
        }
    }

    /**
     * @param string|array           $fields
     * @param \ManaPHP\Mongodb\Model $model
     *
     * @return \ManaPHP\Mongodb\Model\Criteria
     */
    public static function criteria($fields = null, $model = null)
    {
        return Di::getDefault()->get('ManaPHP\Mongodb\Model\Criteria', [$model ?: get_called_class(), $fields]);
    }

    /**
     * @throws \ManaPHP\Mongodb\Model\Exception
     */
    protected function _preCreate()
    {
        if ($this->_id === null) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_id = new ObjectID();
        } elseif (!is_object($this->_id)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->_id = new ObjectID($this->_id);
        }

        $autoIncField = $this->getAutoIncrementField();
        if ($autoIncField !== null && $this->{$autoIncField} === null) {
            $this->{$autoIncField} = $this->generateAutoIncrementId();
        }

        foreach ($this->getFieldTypes() as $field => $type) {
            if ($field === '_id') {
                continue;
            }

            $this->{$field} = $this->getNormalizedValue($type, $this->{$field} !== null ? $this->{$field} : '');
        }
    }

    /**
     * @param array $pipeline
     *
     * @return array
     */
    public static function aggregate($pipeline)
    {
        $instance = new static();
        return $instance->getConnection(null)->aggregate($instance->getSource(null), $pipeline);
    }

    public function __debugInfo()
    {
        return array_merge(['_id' => is_object($this->_id) ? (string)$this->_id : $this->_id], parent::__debugInfo());
    }
}