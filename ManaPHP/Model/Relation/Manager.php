<?php
namespace ManaPHP\Model\Relation;

use ManaPHP\Component;
use ManaPHP\Model\Relation;

class Manager extends Component
{
    /**
     * @var array
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
        if (preg_match('#^(.*?us)|(.*?[sxz])es|(.*?[^aeioudgkprt]h)es$#', $str, $match)) {
            return $match[1];
        } elseif (preg_match('#^(.*?[^aeiou])ies#', $str, $match)) {
            return $match[1] . 'y';
        } else {
            return substr($str, 0, -1);
        }
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

        if ($singular = $this->_pluralToSingular($name)) {
            if (($pos = strrpos($modelName, '\\')) !== false) {
                $className = substr($modelName, 0, $pos + 1) . ucfirst($singular);
            } else {
                $className = ucfirst($singular);
            }

            return [$className, Relation::TYPE_HAS_MANY];
        } else {
            if (in_array($name . '_id', $model->getFields(), true)) {
                if (($pos = strrpos($modelName, '\\')) !== false) {
                    $className = substr($modelName, 0, $pos + 1) . ucfirst($name);
                } else {
                    $className = ucfirst($name);
                }

                if (class_exists($className)) {
                    return [$className, Relation::TYPE_HAS_ONE];
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
        }

        if (!isset($this->_relations[$modelName][$name])) {
            if ($relation = $this->_inferRelation($model, $name)) {
                $this->_relations[$modelName][$name] = $relation;
            }
        }

        if (isset($this->_relations[$modelName][$name])) {
            $relation = $this->_relations[$modelName][$name];
            if ($relation instanceof Relation) {
                return $relation;
            } else {
                if (!isset($relation[1])) {
                    $relation[1] = $this->_isPlural($name) ? Relation::TYPE_HAS_MANY : Relation::TYPE_HAS_ONE;
                }
                return $this->_relations[$modelName][$name] = new Relation($model, $relation);
            }
        }
        return false;
    }
}