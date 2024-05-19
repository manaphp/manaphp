<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Relation;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\AbstractRelation;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;

class HasManyToMany extends AbstractRelation
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    protected string $selfField;
    protected string $thatField;
    protected string $pivotEntity;
    protected string $selfPivot;
    protected string $thatPivot;

    public function __construct(string $selfEntity, string $thatEntity, string $pivotEntity)
    {
        $entityMetadata = Container::get(EntityMetadataInterface::class);

        $this->selfEntity = $selfEntity;
        $this->selfField = $entityMetadata->getPrimaryKey($selfEntity);
        $this->thatEntity = $thatEntity;
        $this->thatField = $entityMetadata->getPrimaryKey($thatEntity);
        $this->pivotEntity = $pivotEntity;
        $this->selfPivot = $entityMetadata->getReferencedKey($selfEntity);
        $this->thatPivot = $entityMetadata->getPrimaryKey($thatEntity);
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        $selfPivot = $this->selfPivot;
        $thatPivot = $this->thatPivot;

        $ids = Arr::unique_column($r, $this->selfField);
        $repository = $this->entityMetadata->getRepository($this->pivotEntity);
        $pivotQuery = $repository->select([$this->selfPivot, $this->thatPivot])->whereIn($this->selfPivot, $ids);
        $pivot_data = $pivotQuery->execute();
        $ids = Arr::unique_column($pivot_data, $this->thatPivot);
        $data = $query->whereIn($this->thatField, $ids)->indexBy($this->thatField)->fetch();

        $rd = [];
        foreach ($pivot_data as $dv) {
            $key = $dv[$thatPivot];

            if (isset($data[$key])) {
                $rd[$dv[$selfPivot]][] = $data[$key];
            }
        }

        foreach ($r as $ri => $rv) {
            $rvr = $rv[$selfPivot];
            $r[$ri][$name] = $rd[$rvr] ?? [];
        }

        return $r;
    }

    public function lazyLoad(Entity $entity): QueryInterface
    {
        $selfField = $this->selfField;
        $pivotRepository = $this->entityMetadata->getRepository($this->pivotEntity);
        $ids = $pivotRepository->values(
            $this->thatPivot, [$this->selfPivot => $entity->$selfField]
        );
        $thatRepository = $this->entityMetadata->getRepository($this->thatEntity);
        return $thatRepository->select()->whereIn($this->thatField, $ids)->setFetchType(true);
    }
}
