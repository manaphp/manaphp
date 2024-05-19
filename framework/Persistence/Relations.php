<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ContainerInterface;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Persistence\Attribute\RelationInterface;
use ManaPHP\Query\QueryInterface;
use function is_array;
use function is_callable;
use function is_string;

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

    public function get(string $entityClass, string $name): ?RelationInterface
    {
        $relations = $this->entityMetadata->getRelations($entityClass);
        return $relations[$name] ?? null;
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
