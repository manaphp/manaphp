<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use Attribute;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\Entity;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;
use function basename;
use function class_exists;
use function str_starts_with;
use function strlen;
use function strrpos;
use function substr;

#[Attribute(Attribute::TARGET_PROPERTY)]
class HasManyToMany extends AbstractRelation
{
    protected string $selfField;
    protected string $pivotEntity;
    protected string $pivotSelfField;
    protected string $pivotThatField;

    public function __construct(string $pivotEntity,
        ?string $thatEntity = null,
        ?string $pivotSelfField = null, ?string $pivotThatField = null
    ) {
        $entityMetadata = Container::get(EntityMetadataInterface::class);

        $this->pivotEntity = $pivotEntity;
        $this->thatEntity = $thatEntity ?? $this->inferThatEntity($pivotEntity, $this->selfEntity);
        $this->pivotSelfField = $pivotSelfField ?? $entityMetadata->getReferencedKey($this->selfEntity);
        $this->pivotThatField = $pivotThatField ?? $entityMetadata->getReferencedKey($this->thatEntity);
    }

    protected function inferThatEntity(string $pivotEntity, string $selfEntity): string
    {
        if (($pos = strrpos($pivotEntity, '\\')) === false) {
            $pivotNamespace = '';
            $pivotSimpleName = $pivotEntity;
        } else {
            $pivotNamespace = substr($pivotEntity, 0, $pos + 1);
            $pivotSimpleName = substr($pivotEntity, $pos + 1);
        }

        if (($pos = strrpos($selfEntity, '\\')) === false) {
            $selfNamespace = '';
            $selfSimpleName = $selfEntity;
        } else {
            $selfNamespace = substr($selfEntity, 0, $pos + 1);
            $selfSimpleName = substr($selfEntity, $pos + 1);
        }

        if (str_starts_with($pivotSimpleName, $selfSimpleName)) {
            $thatSimpleName = substr($pivotSimpleName, strlen($selfSimpleName));
        } else {
            $thatSimpleName = basename($pivotSimpleName, $selfSimpleName);
        }
        $thatEntity = $pivotNamespace . $thatSimpleName;

        return class_exists($thatEntity) ? $thatEntity : ($selfNamespace . $thatSimpleName);
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

        return $this->getThatQuery()
            ->whereIn($this->entityMetadata->getPrimaryKey($this->thatEntity), $ids)
            ->setFetchType(true);
    }
}
