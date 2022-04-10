<?php
declare(strict_types=1);

namespace ManaPHP\Data\Relation;

use ManaPHP\Component;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Data\RelationInterface;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\Data\Model\ThoseInterface $those
 */
class Manager extends Component implements ManagerInterface
{
    protected array $relations;

    public function has(ModelInterface $model, string $name): bool
    {
        return $this->get($model, $name) !== false;
    }

    protected function pluralToSingular(string $str): false|string
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

    protected function inferClassName(ModelInterface $model, string $plainName): false|string
    {
        $plainName = Str::pascalize($plainName);

        $modelName = $model::class;

        if (($pos = strrpos($modelName, '\\')) !== false) {
            $className = substr($modelName, 0, $pos + 1) . $plainName;
            if (class_exists($className)) {
                return $className;
            } elseif (($pos = strpos($modelName, '\Areas\\')) !== false) {
                $className = substr($modelName, 0, $pos) . '\Models\\' . $plainName;
                if (class_exists($className)) {
                    return $className;
                }
            }
            $className = $modelName . $plainName;
        } else {
            $className = $plainName;
        }
        return class_exists($className) ? $className : false;
    }

    protected function inferRelation(ModelInterface $thisInstance, string $name): false|RelationInterface
    {
        if ($thisInstance->hasField($tryName = $name . '_id')) {
            $thatModel = $this->inferClassName($thisInstance, $name);
            return $thatModel ? $thisInstance->belongsTo($thatModel, $tryName) : false;
        } elseif ($thisInstance->hasField($tryName = $name . 'Id')) {
            $thatModel = $this->inferClassName($thisInstance, $name);
            return $thatModel ? $thisInstance->belongsTo($thatModel, $tryName) : false;
        }

        /** @var \ManaPHP\Data\ModelInterface $thatInstance */
        /** @var \ManaPHP\Data\ModelInterface $thatModel */

        if ($singular = $this->pluralToSingular($name)) {
            if (!$thatModel = $this->inferClassName($thisInstance, $singular)) {
                return false;
            }

            $thatInstance = $this->those->get($thatModel);

            $thisForeignedKey = $thisInstance->foreignedKey();
            if ($thatInstance->hasField($thisForeignedKey)) {
                return $thisInstance->hasMany($thatModel, $thisForeignedKey);
            }

            $thatPlain = substr($thatModel, strrpos($thatModel, '\\') + 1);

            $thisModel = $thisInstance::class;
            $pos = strrpos($thisModel, '\\');
            $namespace = substr($thisModel, 0, $pos + 1);
            $thisPlain = substr($thisModel, $pos + 1);

            $pivotModel = $namespace . $thatPlain . $thisPlain;
            if (class_exists($pivotModel)) {
                return $thisInstance->hasManyToMany($thatModel, $pivotModel);
            }

            $pivotModel = $namespace . $thisPlain . $thatPlain;
            if (class_exists($pivotModel)) {
                return $thisInstance->hasManyToMany($thatModel, $pivotModel);
            }

            $thisLen = strlen($thisPlain);
            $thatLen = strlen($thatPlain);
            if ($thisLen > $thatLen) {
                $pos = strpos($thisPlain, $thatPlain);
                if ($pos === 0 || $pos + $thatLen === $thisLen) {
                    return $thisInstance->hasManyOthers($thatModel);
                }
            }

            throw new RuntimeException(['infer `:relation` relation failed', 'relation' => $name]);
        } elseif ($thatModel = $this->inferClassName($thisInstance, $name)) {
            $thatInstance = $this->those->get($thatModel);
            $thisForeignedKey = $thisInstance->foreignedKey();
            $thatForeignedKey = $thatInstance->foreignedKey();
            if ($thatInstance->hasField($thisForeignedKey)) {
                return $thisInstance->hasOne($thatModel, $thisForeignedKey);
            } elseif ($thisInstance->hasField($thatForeignedKey)) {
                return $thisInstance->belongsTo($thatModel, $thatForeignedKey);
            }
        }

        return false;
    }

    protected function isPlural(string $str): bool
    {
        return $str[strlen($str) - 1] === 's';
    }

    public function get(ModelInterface $model, string $name): false|RelationInterface
    {
        $modelName = $model::class;

        $this->relations[$modelName] ??= $model->relations();

        if (isset($this->relations[$modelName][$name])) {
            if (is_object($relation = $this->relations[$modelName][$name])) {
                return $relation;
            } else {
                if ($this->isPlural($name)) {
                    $relation = $model->hasMany($relation);
                } else {
                    $relation = $model->hasOne($relation);
                }
                return $this->relations[$modelName][$name] = $relation;
            }
        } elseif ($relation = $this->inferRelation($model, $name)) {
            return $this->relations[$modelName][$name] = $relation;
        } else {
            return false;
        }
    }

    public function getQuery(ModelInterface $model, string $name, mixed $data): QueryInterface
    {
        $relation = $this->get($model, $name);
        $query = $relation->getThatQuery();

        if ($data === null) {
            null;
        } elseif (is_string($data)) {
            $query->select([$data]);
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
            $data($query);
        } else {
            throw new InvalidValueException(['`:with` with is invalid', 'with' => $name]);
        }

        return $query;
    }

    public function earlyLoad(ModelInterface $model, array $r, array $withs): array
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

            $query = $v instanceof QueryInterface
                ? $v
                : $this->getQuery($model, $name, is_string($k) ? $v : null);

            if ($child_name) {
                $query->with([$child_name]);
            }

            $method = 'get' . ucfirst($name);
            if (method_exists($model, $method)) {
                $query = $model->$method($query);
            }

            $r = $relation->earlyLoad($r, $query, $name);
        }

        return $r;
    }

    public function lazyLoad(ModelInterface $instance, string $relation_name): QueryInterface
    {
        if (($relation = $this->get($instance, $relation_name)) === false) {
            throw new InvalidValueException($relation);
        }

        return $relation->lazyLoad($instance);
    }
}
