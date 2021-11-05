<?php

namespace ManaPHP\Data\Model;

use ManaPHP\Component;
use ManaPHP\Data\Db\Model as DbModel;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @property-read \ManaPHP\Data\Db\Model\MetadataInterface $modelMetadata
 */
class Linter extends Component
{
    const METHOD_FIELDS_BAD = 'bad';
    const METHOD_FIELDS_GOOD = 'good';

    /**
     * @var string
     */
    protected $class;

    /**
     * @var \ReflectionClass
     */
    protected $reflection;

    /**
     * @var \ManaPHP\Data\ModelInterface
     */
    protected $model;

    /**
     * @param string|\ManaPHP\Data\ModelInterface $model
     */
    public function __construct($model)
    {
        $this->class = is_string($model) ? $model : get_class($model);
        /** @noinspection PhpUndefinedMethodInspection */
        $this->model = is_string($model) ? $model::sample() : $model;
        $this->reflection = new ReflectionClass($model);
    }

    /**
     * @return array
     */
    public function lintMethodFields()
    {
        $r = [];
        $model = $this->model;

        foreach ($this->reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $rMethod) {
            if ($rMethod->getDeclaringClass()->getName() !== $this->class) {
                continue;
            }

            $methodName = $rMethod->getName();
            if ($methodName === 'fields') {
                $some = $model->fields();
                if ($model instanceof DbModel) {
                    $all = $this->getPropertyFields();
                    if (!$all) {
                        $all = $this->modelMetadata->getAttributes($model);
                    }
                } else {
                    $all = array_keys($model->fieldTypes());
                }
            } elseif ($methodName === 'intFields') {
                $some = $model->intFields();
                if ($model instanceof DbModel) {
                    $all = $this->modelMetadata->getIntTypeAttributes($model);
                } else {
                    $all = array_keys(
                        array_filter(
                            $model->fieldTypes(), static function ($type) {
                            return $type === 'int';
                        }
                        )
                    );
                }
            } elseif ($methodName === 'safeFields') {
                $some = $model->safeFields();
                $all = $model->fields();
            } elseif ($methodName === 'rules') {
                $some = array_keys($model->rules());
                $all = $model->fields();
            } else {
                continue;
            }

            $r[$methodName][self::METHOD_FIELDS_BAD] = array_diff($some, $all);
            $r[$methodName][self::METHOD_FIELDS_GOOD] = array_diff($all, $some);
        }

        return $r;
    }

    /**
     * @return string[]
     */
    public function getPropertyFields()
    {
        $fields = [];
        foreach ($this->reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $rProperty) {
            if ($rProperty->isStatic()) {
                continue;
            }

            $fields[] = $rProperty->getName();
        }

        return $fields;
    }

    /**
     * @return string[]
     */
    public function lintRealPropertyFields()
    {
        $model = $this->model;

        $properties = [];
        foreach ($this->reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $rProperty) {
            if ($rProperty->isStatic()) {
                continue;
            }

            $properties[] = $rProperty->getName();
        }

        return array_diff($properties, $model->fields());
    }

    /**
     * @return string[]
     */
    public function getMagicFields()
    {
        $comment = $this->reflection->getDocComment();

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

        return array_diff($fields, $this->model->fields());
    }
}
