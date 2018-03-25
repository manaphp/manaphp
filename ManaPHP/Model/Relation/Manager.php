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
     * @param \ManaPHP\Model $model
     * @param string         $name
     *
     * @return \ManaPHP\Model\Relation|false
     */
    public function get($model, $name)
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

        if (isset($this->_definitions[$modelName][$name])) {
            $definition = $this->_definitions[$modelName][$name];
            if (is_object($definition)) {
                return $definition;
            } else {
                return $this->_definitions[$modelName][$name] = new Relation($model, $definition);
            }
        }
        return false;
    }
}