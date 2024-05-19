<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Relation;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\AbstractRelation;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;
use function count;
use function in_array;

class HasManyOthers extends AbstractRelation
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    protected string $selfField;
    protected string $selfValue;

    public function __construct(string $selfEntity, string $thatEntity)
    {
        $entityManager = Container::get(EntityMetadataInterface::class);
        $referencedKey = $entityManager->getReferencedKey($thatEntity);

        $keys = [];
        foreach ($entityManager->getFields($selfEntity) as $field) {
            if ($field === $referencedKey || $field === 'id' || $field === '_id') {
                continue;
            }

            if (!str_ends_with($field, '_id') && !str_ends_with($field, 'Id')) {
                continue;
            }

            if (in_array($field, ['updator_id', 'creator_id'], true)) {
                continue;
            }

            $keys[] = $field;
        }

        if (count($keys) === 1) {
            $selfField = $keys[0];
        } else {
            throw new MisuseException('$thisValue must be not null');
        }

        $this->selfEntity = $selfEntity;
        $this->selfField = $selfField;
        $this->selfValue = $entityManager->getReferencedKey($thatEntity);
        $this->thatEntity = $thatEntity;
    }

    public function earlyLoad(array $r, QueryInterface $thatQuery, string $name): array
    {
        $selfField = $this->selfField;
        $thatField = $this->entityMetadata->getPrimaryKey($this->thatEntity);

        $ids = Arr::unique_column($r, $selfField);
        $repository = $this->entityMetadata->getRepository($this->selfEntity);
        $pivotQuery = $repository->select([$selfField, $this->selfValue])->whereIn($selfField, $ids);
        $pivot_data = $pivotQuery->execute();
        $ids = Arr::unique_column($pivot_data, $this->selfValue);
        $data = $thatQuery->whereIn($thatField, $ids)->indexBy($thatField)->fetch();

        $rd = [];
        foreach ($pivot_data as $dv) {
            $key = $dv[$thatField];

            if (isset($data[$key])) {
                $rd[$dv[$selfField]][] = $data[$key];
            }
        }

        foreach ($r as $ri => $rv) {
            $rvr = $rv[$selfField];
            $r[$ri][$name] = $rd[$rvr] ?? [];
        }

        return $r;
    }

    public function lazyLoad(Entity $entity): QueryInterface
    {
        $selfField = $this->selfField;
        $selfRepository = $this->entityMetadata->getRepository($this->selfEntity);
        $ids = $selfRepository->values($this->selfValue, [$selfField => $entity->$selfField]);
        $thatRepository = $this->entityMetadata->getRepository($this->thatEntity);
        return $thatRepository->select()->whereIn($this->entityMetadata->getPrimaryKey($this->thatEntity), $ids)
            ->setFetchType(true);
    }
}
