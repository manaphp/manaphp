<?php

namespace ManaPHP\Data\Model;

use ManaPHP\Component;
use ManaPHP\Data\Db\Model as DbModel;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @property-read \ManaPHP\Data\Db\Model\MetadataInterface $modelsMetadata
 */
class Linter extends Component
{
    const METHOD_FIELDS_BAD = 'bad';
    const METHOD_FIELDS_GOOD = 'good';

    /**
     * @var string
     */
    protected $_class;

    /**
     * @var \ReflectionClass
     */
    protected $_reflection;

    /**
     * @var \ManaPHP\Data\Db\Model|\ManaPHP\Data\Mongodb\Model
     */
    protected $_model;

    /**
     * @param string|\ManaPHP\Data\Model $model
     */
    public function __construct($model)
    {
        $this->_class = is_string($model) ? $model : get_class($model);
        /** @noinspection PhpUndefinedMethodInspection */
        $this->_model = is_string($model) ? $model::sample() : $model;
        $this->_reflection = new ReflectionClass($model);
    }

    /**
     * @return array
     */
    public function lintMethodFields()
    {
        $r = [];
        $model = $this->_model;

        foreach ($this->_reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $this->_class) {
                continue;
            }

            $methodName = $method->getName();
            if ($methodName === 'getFields') {
                $some = $model->getFields();
                if ($model instanceof DbModel) {
                    $all = $this->getPropertyFields();
                    if (!$all) {
                        $all = $this->modelsMetadata->getAttributes($model);
                    }
                } else {
                    $all = array_keys($model->getFieldTypes());
                }
            } elseif ($methodName === 'getIntFields') {
                $some = $model->getIntFields();
                if ($model instanceof DbModel) {
                    $all = $this->modelsMetadata->getIntTypeAttributes($model);
                } else {
                    $all = array_keys(
                        array_filter(
                            $model->getFieldTypes(), static function ($type) {
                            return $type === 'int';
                        }
                        )
                    );
                }
            } elseif ($methodName === 'getSafeFields') {
                $some = $model->getSafeFields();
                $all = $model->getFields();
            } elseif ($methodName === 'rules') {
                $some = array_keys($model->rules());
                $all = $model->getFields();
            } else {
                continue;
            }

            $r[$methodName][self::METHOD_FIELDS_BAD] = array_diff($some, $all);
            $r[$methodName][self::METHOD_FIELDS_GOOD] = array_diff($all, $some);
        }

        return $r;
    }

    /**
     * @return array
     */
    public function getPropertyFields()
    {
        $fields = [];
        foreach ($this->_reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $fields[] = $property->getName();
        }

        return $fields;
    }

    /**
     * @return array
     */
    public function lintRealPropertyFields()
    {
        $model = $this->_model;

        $properties = [];
        foreach ($this->_reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $properties[] = $property->getName();
        }

        return array_diff($properties, $model->getFields());
    }

    /**
     * @return array
     */
    public function getMagicFields()
    {
        $comment = $this->_reflection->getDocComment();

        if (!$comment) {
            return [];
        }

        $fields = [];

        if (preg_match_all('#\*\s+@property\s+(\S+)\s+\$(\w+)#', $comment, $matches, PREG_SET_ORDER)) {
            foreach ((array)$matches as $match) {
                list(, $type, $field) = $match;
                $fields[$field] = $type;
            }
        }

        return $fields;
    }

    /**
     * @return array
     */
    public function lintMagicPropertyFields()
    {
        $fields = [];
        foreach ($this->getMagicFields() as $field => $type) {
            if (str_contains($type, '\\')) {
                continue;
            }

            $fields[] = $field;
        }

        return array_diff($fields, $this->_model->getFields());
    }
}
