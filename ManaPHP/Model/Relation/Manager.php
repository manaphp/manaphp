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
                return $this->_relations[$modelName][$name] = new Relation($model, $relation);
            }
        }
        return false;
    }
}