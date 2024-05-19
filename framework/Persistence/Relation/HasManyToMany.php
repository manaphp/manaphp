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
    protected string $pivotEntity;
    protected string $selfPivot;
    protected string $thatPivot;

    public function __construct(string $selfEntity, string $thatEntity, string $pivotEntity)
    {
        $entityMetadata = Container::get(EntityMetadataInterface::class);

        $this->selfEntity = $selfEntity;
        $this->thatEntity = $thatEntity;
        $this->pivotEntity = $pivotEntity;
        $this->selfPivot = $entityMetadata->getReferencedKey($selfEntity);
        $this->thatPivot = $entityMetadata->getReferencedKey($thatEntity);
    }

    public function earlyLoad(array $r, QueryInterface $thatQuery, string $name): array
    {
        $selfPivot = $this->selfPivot;
        $thatPivot = $this->thatPivot;

        $ids = Arr::unique_column($r, $this->entityMetadata->getPrimaryKey($this->selfEntity));
        $repository = $this->entityMetadata->getRepository($this->pivotEntity);
        $pivotQuery = $repository->select([$selfPivot, $thatPivot])->whereIn($selfPivot, $ids);
        $pivot_data = $pivotQuery->execute();
        $ids = Arr::unique_column($pivot_data, $thatPivot);

        $thatField = $this->entityMetadata->getPrimaryKey($this->thatEntity);
        $data = $thatQuery->whereIn($thatField, $ids)->indexBy($thatField)->fetch();

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
        $selfField = $this->entityMetadata->getPrimaryKey($this->selfEntity);
        $pivotRepository = $this->entityMetadata->getRepository($this->pivotEntity);
        $ids = $pivotRepository->values(
            $this->thatPivot, [$this->selfPivot => $entity->$selfField]
        );
        $thatRepository = $this->entityMetadata->getRepository($this->thatEntity);
        return $thatRepository->select()
            ->whereIn($this->entityMetadata->getPrimaryKey($this->thatEntity), $ids)->setFetchType(true);
    }
}
