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

class HasOne extends AbstractRelation
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    protected string $thatField;

    public function __construct(string $selfEntity, string $thatEntity, ?string $thatField = null)
    {
        $this->selfEntity = $selfEntity;
        $this->thatEntity = $thatEntity;
        $this->thatField = $thatField ?? Container::get(EntityMetadataInterface::class)->getReferencedKey($selfEntity);
    }

    public function earlyLoad(array $r, QueryInterface $thatQuery, string $name): array
    {
        $selfField = $this->entityMetadata->getPrimaryKey($this->selfEntity);
        $thatField = $this->thatField;

        $ids = Arr::unique_column($r, $selfField);
        $data = $thatQuery->whereIn($thatField, $ids)->indexBy($thatField)->fetch();

        foreach ($r as $ri => $rv) {
            $key = $rv[$selfField];
            $r[$ri][$name] = $data[$key] ?? null;
        }

        return $r;
    }

    public function lazyLoad(Entity $entity): QueryInterface
    {
        $selfField = $this->entityMetadata->getPrimaryKey($this->selfEntity);
        $thatField = $this->thatField;
        $repository = $this->entityMetadata->getRepository($this->thatEntity);
        return $repository->select()->where([$thatField => $entity->$selfField])->setFetchType(false);
    }
}
