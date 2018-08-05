<?php
namespace ManaPHP\Model;

use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;

class Relation implements RelationInterface
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
            /**
             * @var \ManaPHP\Model $reference
             * @var \ManaPHP\Model $referenceModel
             */
            $this->referenceModel = $referenceModel = $definition[0];
            $this->type = $type = $definition[1];
            $referenceField = isset($definition[2]) ? $definition[2] : null;
            if ($type === self::TYPE_BELONGS_TO) {
                $reference = new $referenceModel;
                $this->keyField = $reference->getPrimaryKey();
                $this->valueField = $referenceField ?: $this->_inferReferenceField($model, $referenceModel);
            } elseif ($type === self::TYPE_HAS_ONE) {
                $reference = new $referenceModel;
                $this->keyField = $reference->getPrimaryKey();
                $this->valueField = $referenceField ?: $this->_inferReferenceField($model, $referenceModel);
            } elseif ($type === self::TYPE_HAS_MANY) {
                $this->keyField = $referenceField ?: $this->_inferReferenceField($model, get_class($model));
                $this->valueField = $model->getPrimaryKey();
            } elseif ($type === self::TYPE_HAS_MANY_TO_MANY) {
                $this->keyField = $referenceField ?: $this->_inferReferenceField($model, get_class($model));
                $this->valueField = $model->getPrimaryKey();
            } elseif ($type === self::TYPE_HAS_MANY_VIA) {
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
        /**
         * @var \ManaPHP\ModelInterface $model
         */
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

    /**
     * @param \ManaPHP\Model|array $model
     *
     * @return \ManaPHP\Model\CriteriaInterface
     */
    public function criteria($model)
    {
        $type = $this->type;
        $referenceModel = $this->referenceModel;
        $valueField = $this->valueField;
        if ($type === self::TYPE_HAS_ONE) {
            return $referenceModel::criteria()->where($this->keyField, is_array($model) ? $model[$valueField] : $model->$valueField)->setFetchType(false);
        } elseif ($type === self::TYPE_BELONGS_TO) {
            return $referenceModel::criteria()->where($this->keyField, is_array($model) ? $model[$valueField] : $model->$valueField)->setFetchType(false);
        } elseif ($type === self::TYPE_HAS_MANY) {
            return $referenceModel::criteria()->where($this->keyField, is_array($model) ? $model[$valueField] : $model->$valueField)->setFetchType(true);
        } elseif ($type === self::TYPE_HAS_MANY_TO_MANY) {
            $ids = $model::values($this->keyField, [$valueField => is_array($model) ? $model[$valueField] : $model->$valueField]);
            /**
             * @var \ManaPHP\Model $referenceInstance
             */
            $referenceInstance = is_string($referenceModel) ? new $referenceModel : $referenceModel;
            return $referenceModel::criteria()->where($referenceInstance->getPrimaryKey(), $ids)->setFetchType(true);
        } elseif ($type === self::TYPE_HAS_MANY_VIA) {
            $via = $this->keyField;
            /**
             * @var \ManaPHP\Model $reference
             */
            $reference = new $referenceModel();
            $ids = $via::values($reference->getPrimaryKey(), [$valueField => is_array($model) ? $model[$valueField] : $model->$valueField]);
            return $referenceModel::criteria()->where($reference->getPrimaryKey(), $ids)->setFetchType(true);
        } else {
            throw  new NotSupportedException(['unknown relation type: :type', 'type' => $type]);
        }
    }
}