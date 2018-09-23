<?php
namespace ManaPHP\Model\Relation;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Model\Relation;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var array[]
     */
    protected $_relations;

    /**
     * @param \ManaPHP\Model $model
     * @param string         $name
     *
     * @return bool
     */
    public function has($model, $name)
    {
        return $this->get($model, $name) !== false;
    }

    /**
     * @param string $str
     *
     * @return string|false
     */
    protected function _pluralToSingular($str)
    {
        if ($str[strlen($str) - 1] !== 's') {
            return false;
        }

        //https://github.com/UlvHare/PHPixie-demo/blob/d000d8f11e6ab7c522feeb4457da5a802ca3e0bc/vendor/phpixie/orm/src/PHPixie/ORM/Configs/Inflector.php
        if (preg_match('#^(.*?us)$|(.*?[sxz])es$|(.*?[^aeioudgkprt]h)es$#', $str, $match)) {
            return $match[1];
        } elseif (preg_match('#^(.*?[^aeiou])ies$#', $str, $match)) {
            return $match[1] . 'y';
        } else {
            return substr($str, 0, -1);
        }
    }

    /**
     * @param \ManaPHP\Model $model
     * @param string         $plainName
     *
     * @return string|false
     */
    protected function _inferClassName($model, $plainName)
    {
        $modelName = get_class($model);

        if (($pos = strrpos($modelName, '\\')) !== false) {
            $className = substr($modelName, 0, $pos + 1) . ucfirst($plainName);
        } else {
            $className = ucfirst($plainName);
        }

        return class_exists($className) ? $className : false;
    }

    /**
     * @param \ManaPHP\Model $model
     * @param string         $name
     *
     * @return  array|false
     */
    protected function _inferRelation($model, $name)
    {
        if (in_array($name . '_id', $model->getFields(), true)) {
            $referenceName = $this->_inferClassName($model, $name);
            return $referenceName ? [$referenceName, Relation::TYPE_HAS_ONE] : false;
        }

        if (preg_match('#^(.+[a-z\d])Of([A-Z].*)$#', $name, $match)) {
            if (!$singular = $this->_pluralToSingular($match[1])) {
                return false;
            }

            if (!$referenceName = $this->_inferClassName($model, $singular)) {
                return false;
            }

            $valueField = lcfirst($match[2]) . '_id';
            if (in_array($valueField, $model->getForeignKeys(), true)) {
                /**
                 * @var \ManaPHP\Model $reference
                 */
                $reference = new $referenceName;
                return [$referenceName, Relation::TYPE_HAS_MANY_TO_MANY, $reference->getPrimaryKey(), $valueField];
            } else {
                return false;
            }
        }

        if ($singular = $this->_pluralToSingular($name)) {
            if (!$referenceName = $this->_inferClassName($model, $singular)) {
                return false;
            }

            /**
             * @var \ManaPHP\Model $reference
             */
            $reference = new $referenceName;

            $keys = $model->getForeignKeys();
            if (count($keys) === 2) {
                $foreignKey = $singular . '_id';
                if (in_array($foreignKey, $keys, true)) {
                    $keys = array_flip($keys);
                    unset($keys[$foreignKey]);
                    return [$referenceName, Relation::TYPE_HAS_MANY_TO_MANY, $reference->getPrimaryKey(), key($keys)];
                }
            }
            if (in_array($model->getPrimaryKey(), $reference->getFields(), true)) {
                return [$referenceName, Relation::TYPE_HAS_MANY];
            } else {
                $r1Name = substr($referenceName, strrpos($referenceName, '\\') + 1);

                $modelName = get_class($model);
                $pos = strrpos($modelName, '\\');
                $baseName = substr($modelName, 0, $pos + 1);
                $r2Name = substr($modelName, $pos + 1);

                $tryViaName = $baseName . $r1Name . $r2Name;
                if (class_exists($tryViaName)) {
                    return [$referenceName, Relation::TYPE_HAS_MANY_VIA, $tryViaName, $model->getPrimaryKey()];
                } else {
                    $tryViaName = $baseName . $r2Name . $r1Name;
                    if (!class_exists($tryViaName)) {
                        throw new RuntimeException(['infer `:relation` relation failed', 'relation' => $name]);
                    }

                    return [$referenceName, Relation::TYPE_HAS_MANY_VIA, $tryViaName, $model->getPrimaryKey()];
                }
            }
        }

        return false;
    }

    /**
     * @param string $str
     *
     * @return bool
     */
    protected function _isPlural($str)
    {
        if ($str[strlen($str) - 1] !== 's') {
            return false;
        }

        //https://github.com/UlvHare/PHPixie-demo/blob/d000d8f11e6ab7c522feeb4457da5a802ca3e0bc/vendor/phpixie/orm/src/PHPixie/ORM/Configs/Inflector.php
        return preg_match('#us|[sxz]es|[^aeioudgkprt]hes|[^aeiou]ies$#', $str) === 1;
    }

    /**
     * @param \ManaPHP\Model $model
     * @param string         $name
     *
     * @return \ManaPHP\Model\Relation|false
     */
    public function get($model, $name)
    {
        $modelName = get_class($model);

        if (!isset($this->_relations[$modelName])) {
            $this->_relations[$modelName] = $model->relations();
            foreach ($this->_relations[$modelName] as $k => $v) {
                if (is_int($k)) {
                    $this->_relations[$modelName][$v] = [];
                    unset($this->_relations[$modelName][$k]);
                }
            }
        }

        if (!isset($this->_relations[$modelName][$name]) || !$this->_relations[$modelName][$name]) {
            if ($relation = $this->_inferRelation($model, $name)) {
                $this->_relations[$modelName][$name] = $relation;
            }
        }

        if (isset($this->_relations[$modelName][$name])) {
            $relation = $this->_relations[$modelName][$name];
            if ($relation instanceof Relation) {
                return $relation;
            } else {
                if (is_string($relation)) {
                    $relation = [$relation];
                }

                if (!isset($relation[1])) {
                    $relation[1] = $this->_isPlural($name) ? Relation::TYPE_HAS_MANY : Relation::TYPE_HAS_ONE;
                }
                return $this->_relations[$modelName][$name] = new Relation($model, $relation);
            }
        }

        return false;
    }

    /**
     * @param \ManaPHP\Model\Relation $relation
     * @param mixed                   $context
     *
     * @return \ManaPHP\Model\Criteria|false
     */
    protected function _getRelationCriteria($relation, $context = [])
    {
        /**
         * @var \ManaPHP\Model $referenceModel
         */
        $referenceModel = $relation->referenceModel;
        $criteria = $referenceModel::criteria();
        if ($context === null) {
            null;
        } elseif (is_string($context)) {
            $criteria->select(preg_split('#[\s,]+#', $context, -1, PREG_SPLIT_NO_EMPTY));
        } elseif (is_array($context)) {
            $criteria->select($context);
        } elseif (is_callable($context)) {
            $criteria = $context($criteria);
        } else {
            return false;
        }

        return $criteria;
    }

    /**
     * @param \ManaPHP\Model $model
     * @param array          $r
     * @param array          $withs
     *
     * @return array
     *
     * @throws \ManaPHP\Exception\InvalidValueException
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function bulkPlainBind($model, $r, $withs)
    {
        foreach ($withs as $k => $v) {
            $name = is_int($k) ? $v : $k;
            if (($relation = $this->get($model, $name)) === false) {
                throw new InvalidValueException(['unknown `:relation` relation', 'relation' => $name]);
            }
            $keyField = $relation->keyField;
            $valueField = $relation->valueField;
            if (($criteria = $this->_getRelationCriteria($relation, is_int($k) ? null : $v)) === false) {
                throw new InvalidValueException(['`:with` with is invalid', 'with' => is_int($k) ? $k : $v]);
            }

            if ($relation->type === Relation::TYPE_HAS_ONE || $relation->type === Relation::TYPE_BELONGS_TO) {
                $data = $criteria->where($keyField, array_column($r, $valueField))->indexBy($keyField)->toArray();

                foreach ($r as $ri => $rv) {
                    $rv[$name] = isset($data[$rv[$keyField]]) ? $data[$rv[$keyField]] : null;
                    $r[$ri] = $rv;
                }

                foreach ($r as $ri => $rv) {
                    if (!isset($rv[$name])) {
                        $r[$ri][$rv][$name] = null;
                    }
                }
            } elseif ($relation->type === Relation::TYPE_HAS_MANY) {
                $tr = $r;
                $r = [];
                foreach ($tr as $rv) {
                    $r[$rv[$keyField]] = $rv;
                }
                unset($tr);

                $data = $criteria->where($keyField, array_column($r, $valueField))->toArray();
                foreach ($data as $di => $dv) {
                    $r[$dv[$keyField]][$name][] = $dv;
                }

                foreach ($r as $ri => $rv) {
                    if (!isset($rv[$name])) {
                        $r[$ri][$rv][$name] = [];
                    }
                }

                $r = array_values($r);
            } else {
                throw new NotSupportedException($name);
            }
        }

        return $r;
    }
}