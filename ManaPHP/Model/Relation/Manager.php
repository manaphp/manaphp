<?php
namespace ManaPHP\Model\Relation;

use ManaPHP\Component;
use ManaPHP\Model\Relation;

class Manager extends Component
{
    /**
     * @var array
     */
    protected $_definitions;

    /**
     * @param \ManaPHP\Model $model
     * @param string         $name
     *
     * @return bool
     */
    public function has($model, $name)
    {

    }

    /**
     * @param \ManaPHP\Model $model
     * @param string         $name
     *
     * @return  array|false
     */
    protected function _inferRelation($model, $name)
    {
        $modelName = get_class($model);

        if (in_array($name . '_id', $model->getFields(), true)) {
            if (($pos = strrpos($modelName, '\\')) !== false) {
                $className = substr($modelName, 0, $pos + 1) . ucfirst($name);
            } else {
                $className = ucfirst($name);
            }

            if (class_exists($className)) {
                return [Relation::TYPE_HAS_ONE, $className];
            }
        } elseif (preg_match('#^(.*?)(ies|es|s)$#', $name, $match)) {
            if ($match[2] === 'ies') {
                $plainClassName = $match[1] . 'y';
            } else {
                $plainClassName = $match[1];
            }

            if (($pos = strrpos($modelName, '\\')) !== false) {
                $className = substr($modelName, 0, $pos + 1) . ucfirst($plainClassName);
            } else {
                $className = ucfirst($plainClassName);
            }

            return [Relation::TYPE_HAS_MANY, $className];
        }

        return false;
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

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new RelationException(['infer referenceField from `:model` failed.', 'model' => $model]);
    }

    /**
     * @param \ManaPHP\Model $model
     * @param string         $name
     *
     * @return array|false
     */
    public function getDefinition($model, $name)
    {
        $modelName = get_class($model);

        if (!isset($this->_definitions[$modelName])) {
            $this->_definitions[$modelName] = $model->relations();
        }

        if (!isset($this->_definitions[$modelName][$name])) {
            if ($definition = $this->_inferRelation($model, $name)) {
                $this->_definitions[$modelName][$name] = $definition;
            }
        }

        /**
         * @var \ManaPHP\Model $reference
         * @var \ManaPHP\Model $referenceModel
         */

        if (isset($this->_definitions[$modelName][$name])) {
            $definition = $this->_definitions[$modelName][$name];
            if (count($definition) !== 4) {
                $type = $definition[0];

                $referenceModel = $definition[1];
                $referenceField = isset($definition[2]) ? $definition[2] : null;

                if ($type === Relation::TYPE_BELONGS_TO) {
                    $reference = new $referenceModel;
                    $keyField = $reference->getPrimaryKey();
                    $valueField = $referenceField ?: $this->_inferReferenceField($model, $referenceModel);
                    return $this->_definitions[$modelName][$name] = [$type, $referenceModel, $keyField, $valueField];
                } elseif ($type === Relation::TYPE_HAS_ONE) {
                    $reference = new $referenceModel;
                    $keyField = $reference->getPrimaryKey();
                    $valueField = $referenceField ?: $this->_inferReferenceField($model, $referenceModel);
                    return $this->_definitions[$modelName][$name] = [$type, $referenceModel, $keyField, $valueField];
                } elseif ($type === Relation::TYPE_HAS_MANY) {
                    $reference = new $referenceModel;

                    $keyField = $referenceField ?: $this->_inferReferenceField($model, get_class($model));
                    $valueField = $model->getPrimaryKey();
                    return $this->_definitions[$modelName][$name] = [$type, $referenceModel, $keyField, $valueField, $reference->getPrimaryKey()];
                } elseif ($type === Relation::TYPE_HAS_MANY_TO_MANY) {
                    $reference = new $referenceModel;
                    $keyField = $referenceField ?: $this->_inferReferenceField($model, get_class($model));
                    $valueField = $model->getPrimaryKey();
                    return $this->_definitions[$modelName][$name] = [$type, $referenceModel, $keyField, $valueField, $reference->getPrimaryKey()];
                }


            } else {
                return $definition;
            }
        }
        return false;
    }

    /**
     * @param \ManaPHP\Model $model
     * @param string         $name
     *
     * @return \ManaPHP\Model\Criteria|false
     */
    public function getCriteria($model, $name)
    {
        $definition = $this->getDefinition($model, $name);
        if (!$definition) {
            return false;
        }

        list($type, $referenceModel, $keyField, $valueField) = $definition;

        /**
         * @var \ManaPHP\Model $referenceModel
         */
        if ($type === Relation::TYPE_BELONGS_TO) {
            return $referenceModel::criteria()->where($keyField, $model->$valueField)->setFetchType(false);
        } elseif ($type === Relation::TYPE_HAS_ONE) {
            return $referenceModel::criteria()->where($keyField, $model->$valueField)->setFetchType(false);
        } elseif ($type === Relation::TYPE_HAS_MANY) {
            return $referenceModel::criteria()->where($keyField, $model->$valueField)->indexBy($definition[4])->setFetchType(true);
        } elseif ($type === Relation::TYPE_HAS_MANY_TO_MANY) {
            return $referenceModel::criteria()->where($keyField, $model->$valueField)->indexBy($definition[4])->setFetchType(true);
        }
    }
}