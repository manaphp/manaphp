<?php
namespace ManaPHP\Model;

use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Exception\NotSupportedException;

class Relation
{
    const TYPE_BELONGS_TO = 1;
    const TYPE_HAS_MANY = 2;
    const TYPE_HAS_ONE = 3;
    const TYPE_HAS_MANY_TO_MANY = 4;
    const TYPE_HAS_MANY_VIA = 5;

    /**
     * @var int
     */
    public $type;

    /**
     * @var string|\ManaPHP\Model
     */
    public $referenceModel;

    /**
     * @var string
     */
    public $keyField;

    /**
     * @var string
     */
    public $valueField;

    /**
     * Relation constructor.
     *
     * @param \ManaPHP\Model $model
     * @param array          $definition
     */
    public function __construct($model, $definition = null)
    {
        if (count($definition) >= 4) {
            $this->referenceModel = $definition[0];
            $this->type = $definition[1];
            $this->keyField = $definition[2];
            $this->valueField = $definition[3];
        } else {
            /** @var \ManaPHP\Model $reference */
            /** @var \ManaPHP\Model $referenceModel */
            $this->referenceModel = $referenceModel = $definition[0];
            $this->type = $type = $definition[1];
            $referenceField = $definition[2] ?? null;
            if ($type === self::TYPE_BELONGS_TO) {
                $reference = $referenceModel::sample();
                $this->keyField = $reference->getPrimaryKey();
                $this->valueField = $referenceField ?: $this->_inferReferenceField($model, $referenceModel);
            } elseif ($type === self::TYPE_HAS_ONE) {
                $reference = $referenceModel::sample();
                $this->keyField = $reference->getPrimaryKey();
                $this->valueField = $referenceField ?: $this->_inferReferenceField($model, $referenceModel);
            } elseif ($type === self::TYPE_HAS_MANY) {
                $this->keyField = $referenceField ?: $this->_inferReferenceField($model, get_class($model));
                $this->valueField = $model->getPrimaryKey();
            } elseif ($type === self::TYPE_HAS_MANY_TO_MANY) {
                $this->keyField = $referenceField ?: $this->_inferReferenceField($model, get_class($model));
                $this->valueField = $model->getPrimaryKey();
            } elseif ($type === self::TYPE_HAS_MANY_VIA) {
                if ($referenceField === null) {
                    $modelName = get_class($model);

                    if (class_exists($try = $modelName . substr($referenceModel, strrpos($referenceModel, '\\') + 1))
                        || class_exists($try = $referenceModel . substr($modelName, strrpos($modelName, '\\') + 1))) {
                        $referenceField = $try;
                    } else {
                        throw new MissingFieldException('MANY_VIA relation must provide VIA class name(3rd parameter)');
                    }
                }

                $this->keyField = $referenceField;
                $this->valueField = $model->getPrimaryKey();
            } else {
                throw  new InvalidValueException(['unknown relation type: :type', 'type' => $type]);
            }
        }
    }

    /**
     * @param \ManaPHP\Model $primaryModel
     * @param string         $model
     *
     * @return string
     */
    protected function _inferReferenceField($primaryModel, $model)
    {
        /** @var \ManaPHP\ModelInterface $model */
        $modelTail = ($pos = strrpos($model, '\\')) !== false ? substr($model, $pos + 1) : $model;
        $tryField = lcfirst($modelTail) . '_id';
        $fields = $primaryModel->getFields();
        if (in_array($tryField, $fields, true)) {
            return $tryField;
        } elseif (preg_match('#([A-Z][a-z]*)$#', $modelTail, $match) === 1) {
            $tryField = $match[1] . '_id';
            /** @noinspection NotOptimalIfConditionsInspection */
            if (in_array($tryField, $fields, true)) {
                return $tryField;
            }
        }

        throw new NotSupportedException(['infer referenceField from `:model` failed.', 'model' => $model]);
    }
}