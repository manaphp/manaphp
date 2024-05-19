<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ContainerInterface;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Helper\Str;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Persistence\Relation\BelongsTo;
use ManaPHP\Persistence\Relation\HasMany;
use ManaPHP\Persistence\Relation\HasManyOthers;
use ManaPHP\Persistence\Relation\HasManyToMany;
use ManaPHP\Persistence\Relation\HasOne;
use ManaPHP\Query\QueryInterface;
use function is_array;
use function is_callable;
use function is_string;
use function strlen;

class Relations implements RelationsInterface
{
    #[Autowired] protected ThoseInterface $those;
    #[Autowired] protected EntityMetadataInterface $entityMetadata;
    #[Autowired] protected ContainerInterface $container;

    protected array $relations;

    public function has(string $entityClass, string $name): bool
    {
        return $this->get($entityClass, $name) !== null;
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

    protected function inferClassName(string $entityClass, string $plainName): ?string
    {
        $plainName = Str::pascalize($plainName);

        if (($pos = strrpos($entityClass, '\\')) !== false) {
            $className = substr($entityClass, 0, $pos + 1) . $plainName;
            if (class_exists($className)) {
                return $className;
            } elseif (($pos = strpos($entityClass, '\Areas\\')) !== false) {
                $className = substr($entityClass, 0, $pos) . '\Entities\\' . $plainName;
                if (class_exists($className)) {
                    return $className;
                }
            }
            $className = $entityClass . $plainName;
        } else {
            $className = $plainName;
        }
        return class_exists($className) ? $className : null;
    }

    protected function inferRelation(string $selfModel, string $name): ?RelationInterface
    {
        if (property_exists($selfModel, $name . '_id')) {
            $thatModel = $this->inferClassName($selfModel, $name);
            return $thatModel ? new BelongsTo($selfModel, $thatModel) : null;
        } elseif (property_exists($selfModel, $name . 'Id')) {
            $thatModel = $this->inferClassName($selfModel, $name);
            return $thatModel ? new BelongsTo($selfModel, $thatModel) : null;
        }

        if ($singular = $this->pluralToSingular($name)) {
            if (!$thatModel = $this->inferClassName($selfModel, $singular)) {
                return null;
            }

            $selfReferencedKey = $this->entityMetadata->getReferencedKey($selfModel);
            if (property_exists($thatModel, $selfReferencedKey)) {
                return new HasMany($selfModel, $thatModel);
            }

            $thatPlain = substr($thatModel, strrpos($thatModel, '\\') + 1);

            $pos = strrpos($selfModel, '\\');
            $namespace = substr($selfModel, 0, $pos + 1);
            $selfPlain = substr($selfModel, $pos + 1);

            $pivotModel = $namespace . $thatPlain . $selfPlain;
            if (class_exists($pivotModel)) {
                return new HasManyToMany($selfModel, $thatModel, $pivotModel);
            }

            $pivotModel = $namespace . $selfPlain . $thatPlain;
            if (class_exists($pivotModel)) {
                return new HasManyToMany($selfModel, $thatModel, $pivotModel);
            }

            $selfLen = strlen($selfPlain);
            $thatLen = strlen($thatPlain);
            if ($selfLen > $thatLen) {
                $pos = strpos($selfPlain, $thatPlain);
                if ($pos === 0 || $pos + $thatLen === $selfLen) {
                    return new HasManyOthers($selfModel, $thatModel, '??');
                }
            }

            throw new RuntimeException(['infer `{relation}` relation failed', 'relation' => $name]);
        } elseif ($thatModel = $this->inferClassName($selfModel, $name)) {
            $selfReferencedKey = $this->entityMetadata->getReferencedKey($selfModel);
            $thatReferencedKey = $this->entityMetadata->getReferencedKey($thatModel);
            if (property_exists($thatModel, $selfReferencedKey)) {
                return new HasOne($selfModel, $thatModel);
            } elseif (property_exists($selfModel, $thatReferencedKey)) {
                return new BelongsTo($selfModel, $thatModel);
            }
        }

        return null;
    }

    protected function isPlural(string $str): bool
    {
        return $str[strlen($str) - 1] === 's';
    }

    public function get(string $entityClass, string $name): ?RelationInterface
    {
        $instance = $this->those->get($entityClass);
        if (!isset($this->relations[$entityClass])) {
            $this->relations[$entityClass] = [];
            foreach ($instance->relations() as $relation_name => $relation) {
                $this->relations[$entityClass][$relation_name] = $this->container->injectProperties($relation);
            }
        }

        if (($relation = $this->relations[$entityClass][$name] ?? null) !== null) {
            return $relation;
        } elseif ($relation = $this->inferRelation($entityClass, $name)) {
            return $this->relations[$entityClass][$name] = $this->container->injectProperties($relation);
        } else {
            return null;
        }
    }

    public function getThatQuery(string $entityClass, string $name, mixed $data): QueryInterface
    {
        $relation = $this->get($entityClass, $name);
        /** @noinspection NullPointerExceptionInspection */
        $query = $relation->getThatQuery();

        if ($data === null) {
            SuppressWarnings::noop();
        } elseif (is_string($data)) {
            $query->select(preg_split('#[,\s]+#', $data, -1, PREG_SPLIT_NO_EMPTY));
        } elseif (is_array($data)) {
            $query->select($data);
        } elseif (is_callable($data)) {
            $data($query);
        } elseif ($data instanceof AdditionalRelationCriteria) {
            $query->select($data->getFields());
            $query->orderBy($data->getOrders());
        } else {
            throw new InvalidValueException(['`{with}` with is invalid', 'with' => $name]);
        }

        return $query;
    }

    public function earlyLoad(string $entityClass, array $r, array $withs): array
    {
        foreach ($withs as $k => $v) {
            $name = is_string($k) ? $k : $v;
            if ($pos = strpos($name, '.')) {
                $child_name = substr($name, $pos + 1);
                $name = substr($name, 0, $pos);
            } else {
                $child_name = null;
            }

            if (($relation = $this->get($entityClass, $name)) === null) {
                throw new InvalidValueException(['unknown `{relation}` relation', 'relation' => $name]);
            }

            $thatQuery = $v instanceof QueryInterface
                ? $v
                : $this->getThatQuery($entityClass, $name, is_string($k) ? $v : null);

            if ($child_name) {
                $thatQuery->with([$child_name]);
            }

            $method = 'get' . ucfirst($name);
            if (method_exists($entityClass, $method)) {
                $thatQuery = $this->those->get($entityClass)->$method($thatQuery);
            }

            $r = $relation->earlyLoad($r, $thatQuery, $name);
        }

        return $r;
    }

    public function lazyLoad(Entity $entity, string $relation_name): QueryInterface
    {
        if (($relation = $this->get($entity::class, $relation_name)) === null) {
            throw new InvalidValueException($relation_name);
        }

        return $relation->lazyLoad($entity);
    }
}
