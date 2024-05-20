<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use Attribute;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class HasManyOthers extends AbstractRelation
{
    protected string $selfField;
    protected string $selfValue;

    public function __construct(string $thatEntity, string $selfField, ?string $selfValue = null)
    {
        $this->thatEntity = $thatEntity;
        $this->selfField = $selfField;
        $this->selfValue = $selfValue ?? Container::get(EntityMetadataInterface::class)->getReferencedKey($thatEntity);
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
