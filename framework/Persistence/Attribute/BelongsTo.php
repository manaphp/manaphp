<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use Attribute;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo extends AbstractRelation
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    protected string $selfField;

    public function __construct(?string $selfField = null)
    {
        $this->selfField = $selfField ?? Container::get(EntityMetadataInterface::class)->getReferencedKey($this->thatEntity);
    }

    public function earlyLoad(array $r, QueryInterface $thatQuery, string $name): array
    {
        $selfField = $this->selfField;
        $thatField = $this->entityMetadata->getPrimaryKey($this->thatEntity);

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
        $selfField = $this->selfField;
        $thatField = $this->entityMetadata->getPrimaryKey($this->thatEntity);
        $repository = $this->entityMetadata->getRepository($this->thatEntity);
        return $repository->select()->where([$thatField => $entity->$selfField])->setFetchType(false);
    }
}
