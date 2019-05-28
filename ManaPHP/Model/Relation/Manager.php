<?php
namespace ManaPHP\Model\Relation;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Model\Relation;
use ManaPHP\QueryInterface;

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
                /** @var \ManaPHP\Model $reference */
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

            /** @var \ManaPHP\Model $reference */
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
        return $str[strlen($str) - 1] === 's';
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
        }

        if (isset($this->_relations[$modelName][$name])) {
            if (($relation = $this->_relations[$modelName][$name]) instanceof Relation) {
                return $relation;
            }
        } elseif ($relation = $this->_inferRelation($model, $name)) {
            $this->_relations[$modelName][$name] = $relation;
        } else {
            return false;
        }

        if (is_string($relation)) {
            $relation = [$relation];
        }

        if (!isset($relation[1])) {
            $relation[1] = $this->_isPlural($name) ? Relation::TYPE_HAS_MANY : Relation::TYPE_HAS_ONE;
        }
        return $this->_relations[$modelName][$name] = new Relation($model, $relation);
    }

    /**
     * @param \ManaPHP\Model          $model
     * @param                         $name
     * @param                         $data
     *
     * @return \ManaPHP\QueryInterface
     */
    public function getQuery($model, $name, $data)
    {
        $relation = $this->get($model, $name);
        /** @var \ManaPHP\Model $referenceModel */
        $referenceModel = $relation->referenceModel;
        $query = $referenceModel::query();

        if ($data === null) {
            null;
        } elseif (is_string($data)) {
            $query->select($data);
        } elseif (is_array($data)) {
            if ($data) {
                if (isset($data[count($data) - 1])) {
                    $query->select(count($data) > 1 ? $data : $data[0]);
                } elseif (isset($data[0])) {
                    $query->select($data[0]);
                    unset($data[0]);
                    $query->where($data);
                } else {
                    $query->where($data);
                }
            }
        } elseif (is_callable($data)) {
            $query = $data($query);
        } else {
            throw new InvalidValueException(['`:with` with is invalid', 'with' => $name]);
        }

        return $query;
    }

    /**
     * @param \ManaPHP\Model $model
     * @param array          $r
     * @param array          $withs
     * @param bool           $asArray
     *
     * @return array
     *
     * @throws \ManaPHP\Exception\InvalidValueException
     * @throws \ManaPHP\Exception\NotSupportedException
     */
    public function earlyLoad($model, $r, $withs, $asArray)
    {
        foreach ($withs as $k => $v) {
            $name = is_string($k) ? $k : $v;
            if ($pos = strpos($name, '.')) {
                $child_name = substr($name, $pos + 1);
                $name = substr($name, 0, $pos);
            } else {
                $child_name = null;
            }

            if (($relation = $this->get($model, $name)) === false) {
                throw new InvalidValueException(['unknown `:relation` relation', 'relation' => $name]);
            }
            $keyField = $relation->keyField;
            $valueField = $relation->valueField;

            $query = $v instanceof QueryInterface ? $v : $this->getQuery($model, $name, is_string($k) ? $v : null);

            if ($child_name) {
                $query->with([$child_name]);
            }

            $method = 'get' . ucfirst($name);
            if (method_exists($model, $method)) {
                $query = $model->$method($query);
            }

            if ($relation->type === Relation::TYPE_HAS_ONE || $relation->type === Relation::TYPE_BELONGS_TO) {
                $ids = array_values(array_unique(PHP_MAJOR_VERSION >= 7 ? array_column($r, $valueField) : array_field($r, $valueField)));
                $data = $query->whereIn($keyField, $ids)->indexBy($keyField)->fetch($asArray);

                foreach ($r as $ri => $rv) {
                    $key = $rv[$valueField];
                    $r[$ri][$name] = isset($data[$key]) ? $data[$key] : null;
                }
            } elseif ($relation->type === Relation::TYPE_HAS_MANY) {
                $r_index = [];
                foreach ($r as $ri => $rv) {
                    $r_index[$rv[$valueField]] = $ri;
                }

                $ids = PHP_MAJOR_VERSION >= 7 ? array_column($r, $valueField) : array_field($r, $valueField);
                $data = $query->whereIn($keyField, $ids)->fetch($asArray);

                if (isset($data[0]) && !isset($data[0][$relation->keyField])) {
                    throw new MisuseException(['missing `:field` field in `:name` with', 'field' => $relation->keyField, 'name' => $name]);
                }

                $rd = [];
                foreach ($data as $dv) {
                    $rd[$r_index[$dv[$keyField]]][] = $dv;
                }

                foreach ($r as $ri => $rv) {
                    $r[$ri][$name] = isset($rd[$ri]) ? $rd[$ri] : [];
                }
            } elseif ($relation->type === Relation::TYPE_HAS_MANY_TO_MANY) {
                $ids = PHP_MAJOR_VERSION >= 7 ? array_column($r, $valueField) : array_field($r, $valueField);
                $via_data = $model::query()->select([$keyField, $valueField])->whereIn($valueField, $ids)->execute();
                $data = $query->where($query->getModel()->getPrimaryKey(),
                    array_ufield($via_data, $keyField))->indexBy($query->getModel()->getPrimaryKey())->fetch($asArray);

                $rd = [];
                foreach ($via_data as $dv) {
                    $key = $dv[$keyField];

                    if (isset($data[$key])) {
                        $rd[$dv[$valueField]][] = $data[$key];
                    }
                }

                foreach ($r as $ri => $rv) {
                    $value = $rv[$valueField];
                    $r[$ri][$name] = isset($rd[$value]) ? $rd[$value] : [];
                }
            } elseif ($relation->type === Relation::TYPE_HAS_MANY_VIA) {
                /** @var \ManaPHP\ModelInterface $via */
                $via = $relation->keyField;
                /** @var \ManaPHP\ModelInterface $reference */
                $reference = new $relation->referenceModel;
                $keyField = $reference->getPrimaryKey();
                $via_data = $via::query()->select([$keyField, $relation->valueField])->where($valueField, array_ufield($r, $model->getPrimaryKey()))->execute();
                $ids = array_ufield($via_data, $keyField);
                $data = $query->where($query->getModel()->getPrimaryKey(), $ids)->indexBy($query->getModel()->getPrimaryKey())->fetch($asArray);

                $rd = [];
                foreach ($via_data as $dv) {
                    $key = $dv[$keyField];

                    if (isset($data[$key])) {
                        $rd[$dv[$valueField]][] = $data[$key];
                    }
                }

                foreach ($r as $ri => $_) {
                    $r[$ri][$name] = isset($rd[$ri]) ? $rd[$ri] : [];
                }
            } else {
                throw new NotSupportedException($name);
            }
        }

        return $r;
    }

    /**
     * @param \ManaPHP\Model $instance
     * @param string         $relation_name
     *
     * @return \ManaPHP\QueryInterface
     */
    public function lazyLoad($instance, $relation_name)
    {
        if (($relation = $this->get($instance, $relation_name)) === false) {
            throw new InvalidValueException($relation);
        }

        $type = $relation->type;
        $referenceModel = $relation->referenceModel;
        $valueField = $relation->valueField;
        if ($type === Relation::TYPE_HAS_ONE) {
            return $referenceModel::query()->whereEq($relation->keyField, $instance->$valueField)->setFetchType(false);
        } elseif ($type === Relation::TYPE_BELONGS_TO) {
            return $referenceModel::query()->whereEq($relation->keyField, $instance->$valueField)->setFetchType(false);
        } elseif ($type === Relation::TYPE_HAS_MANY) {
            return $referenceModel::query()->whereEq($relation->keyField, $instance->$valueField)->setFetchType(true);
        } elseif ($type === Relation::TYPE_HAS_MANY_TO_MANY) {
            $ids = $instance::values($relation->keyField, [$valueField => $instance->$valueField]);
            /** @var \ManaPHP\Model $referenceInstance */
            $referenceInstance = is_string($referenceModel) ? new $referenceModel : $referenceModel;
            return $referenceModel::query()->whereIn($referenceInstance->getPrimaryKey(), $ids)->setFetchType(true);
        } elseif ($type === Relation::TYPE_HAS_MANY_VIA) {
            $via = $relation->keyField;
            /** @var \ManaPHP\Model $reference */
            $reference = new $referenceModel();
            $ids = $via::values($reference->getPrimaryKey(), [$valueField => $instance->$valueField]);
            return $referenceModel::query()->whereIn($reference->getPrimaryKey(), $ids)->setFetchType(true);
        } else {
            throw  new NotSupportedException(['unknown relation type: :type', 'type' => $type]);
        }
    }
}