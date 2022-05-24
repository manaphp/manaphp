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
 * @property-read \ManaPHP\Data\Model\ThoseInterface   $those
 * @property-read \ManaPHP\Data\Model\ManagerInterface $modelManager
 */
class Manager extends Component implements ManagerInterface
{
    protected array $relations;

    public function has(string $model, string $name): bool
    {
        return $this->get($model, $name) !== null;
    }

    protected function pluralToSingular(string $str): ?string
    {
        if ($str[strlen($str) - 1] !== 's') {
            return null;
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

    protected function inferClassName(string $model, string $plainName): ?string
    {
        $plainName = Str::pascalize($plainName);

        if (($pos = strrpos($model, '\\')) !== false) {
            $className = substr($model, 0, $pos + 1) . $plainName;
            if (class_exists($className)) {
                return $className;
            } elseif (($pos = strpos($model, '\Areas\\')) !== false) {
                $className = substr($model, 0, $pos) . '\Models\\' . $plainName;
                if (class_exists($className)) {
                    return $className;
                }
            }
            $className = $model . $plainName;
        } else {
            $className = $plainName;
        }
        return class_exists($className) ? $className : null;
    }

    protected function inferRelation(string $thisModel, string $name): ?RelationInterface
    {
        if (property_exists($thisModel, $name . '_id')) {
            $thatModel = $this->inferClassName($thisModel, $name);
            return $thatModel ? new BelongsTo($thisModel, $thatModel) : null;
        } elseif (property_exists($thisModel, $name . 'Id')) {
            $thatModel = $this->inferClassName($thisModel, $name);
            return $thatModel ? new BelongsTo($thisModel, $thatModel) : null;
        }

        /** @var \ManaPHP\Data\ModelInterface $thatInstance */
        /** @var \ManaPHP\Data\ModelInterface $thatModel */

        if ($singular = $this->pluralToSingular($name)) {
            if (!$thatModel = $this->inferClassName($thisModel, $singular)) {
                return null;
            }

            $thisForeignedKey = $this->modelManager->getForeignedKey($thisModel);
            if (property_exists($thatModel, $thisForeignedKey)) {
                return new HasMany($thisModel, $thatModel);
            }

            $thatPlain = substr($thatModel, strrpos($thatModel, '\\') + 1);

            $pos = strrpos($thisModel, '\\');
            $namespace = substr($thisModel, 0, $pos + 1);
            $thisPlain = substr($thisModel, $pos + 1);

            $pivotModel = $namespace . $thatPlain . $thisPlain;
            if (class_exists($pivotModel)) {
                return new HasManyToMany($thisModel, $thatModel, $pivotModel);
            }

            $pivotModel = $namespace . $thisPlain . $thatPlain;
            if (class_exists($pivotModel)) {
                return new HasManyToMany($thisModel, $thatModel, $pivotModel);
            }

            $thisLen = strlen($thisPlain);
            $thatLen = strlen($thatPlain);
            if ($thisLen > $thatLen) {
                $pos = strpos($thisPlain, $thatPlain);
                if ($pos === 0 || $pos + $thatLen === $thisLen) {
                    return new HasManyOthers($thisModel, $thatModel);
                }
            }

            throw new RuntimeException(['infer `:relation` relation failed', 'relation' => $name]);
        } elseif ($thatModel = $this->inferClassName($thisModel, $name)) {
            $thisForeignedKey = $this->modelManager->getForeignedKey($thisModel);
            $thatForeignedKey = $this->modelManager->getForeignedKey($thatModel);
            if (property_exists($thatModel, $thisForeignedKey)) {
                return new HasOne($thisModel, $thatModel);
            } elseif (property_exists($thisModel, $thatForeignedKey)) {
                return new BelongsTo($thisModel, $thatModel);
            }
        }

        return null;
    }

    protected function isPlural(string $str): bool
    {
        return $str[strlen($str) - 1] === 's';
    }

    public function get(string $model, string $name): ?RelationInterface
    {
        $instance = $this->those->get($model);
        $this->relations[$model] ??= $instance->relations();

        if (($relation = $this->relations[$model][$name] ?? null) !== null) {
            return $relation;
        } elseif ($relation = $this->inferRelation($model, $name)) {
            return $this->relations[$model][$name] = $relation;
        } else {
            return null;
        }
    }

    public function getQuery(string $model, string $name, mixed $data): QueryInterface
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

    public function earlyLoad(string $model, array $r, array $withs): array
    {
        foreach ($withs as $k => $v) {
            $name = is_string($k) ? $k : $v;
            if ($pos = strpos($name, '.')) {
                $child_name = substr($name, $pos + 1);
                $name = substr($name, 0, $pos);
            } else {
                $child_name = null;
            }

            if (($relation = $this->get($model, $name)) === null) {
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
                $query = $this->those->get($model)->$method($query);
            }

            $r = $relation->earlyLoad($r, $query, $name);
        }

        return $r;
    }

    public function lazyLoad(ModelInterface $instance, string $relation_name): QueryInterface
    {
        if (($relation = $this->get($instance::class, $relation_name)) === null) {
            throw new InvalidValueException($relation_name);
        }

        return $relation->lazyLoad($instance);
    }
}
