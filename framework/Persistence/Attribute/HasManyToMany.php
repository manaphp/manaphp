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
class HasManyToMany extends AbstractRelation
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    protected string $selfField;
    protected string $pivotEntity;
    protected string $pivotSelfField;
    protected string $pivotThatField;

    public function __construct(string $thatEntity,
        string $pivotEntity, ?string $pivotSelfField = null, ?string $pivotThatField = null
    ) {
        $entityMetadata = Container::get(EntityMetadataInterface::class);

        $this->thatEntity = $thatEntity;
        $this->pivotEntity = $pivotEntity;
        $this->pivotSelfField = $pivotSelfField ?? $entityMetadata->getReferencedKey($this->selfEntity);
        $this->pivotThatField = $pivotThatField ?? $entityMetadata->getReferencedKey($thatEntity);
    }

    public function earlyLoad(array $r, QueryInterface $thatQuery, string $name): array
    {
        $pivotSelfField = $this->pivotSelfField;
        $pivotThatField = $this->pivotThatField;

        $ids = Arr::unique_column($r, $this->entityMetadata->getPrimaryKey($this->selfEntity));
        $repository = $this->entityMetadata->getRepository($this->pivotEntity);
        $pivotQuery = $repository->select([$pivotSelfField, $pivotThatField])->whereIn($pivotSelfField, $ids);
        $pivot_data = $pivotQuery->execute();
        $ids = Arr::unique_column($pivot_data, $pivotThatField);

        $thatField = $this->entityMetadata->getPrimaryKey($this->thatEntity);
        $data = $thatQuery->whereIn($thatField, $ids)->indexBy($thatField)->fetch();

        $rd = [];
        foreach ($pivot_data as $dv) {
            $key = $dv[$pivotThatField];

            if (isset($data[$key])) {
                $rd[$dv[$pivotSelfField]][] = $data[$key];
            }
        }

        foreach ($r as $ri => $rv) {
            $rvr = $rv[$pivotSelfField];
            $r[$ri][$name] = $rd[$rvr] ?? [];
        }

        return $r;
    }

    public function lazyLoad(Entity $entity): QueryInterface
    {
        $selfField = $this->entityMetadata->getPrimaryKey($this->selfEntity);
        $pivotRepository = $this->entityMetadata->getRepository($this->pivotEntity);
        $ids = $pivotRepository->values(
            $this->pivotThatField, [$this->pivotSelfField => $entity->$selfField]
        );
        $thatRepository = $this->entityMetadata->getRepository($this->thatEntity);
        return $thatRepository->select()
            ->whereIn($this->entityMetadata->getPrimaryKey($this->thatEntity), $ids)->setFetchType(true);
    }
}
