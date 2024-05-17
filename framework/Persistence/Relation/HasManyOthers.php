<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Relation;

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
    protected string $selfFilter;
    protected string $selfValue;
    protected string $thatField;

    public function __construct(string $selfModel, string $thatModel)
    {
        $entityManager = Container::get(EntityMetadataInterface::class);
        $referencedKey = $entityManager->getReferencedKey($thatModel);

        $keys = [];
        foreach ($entityManager->getFields($selfModel) as $field) {
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
            $selfFilter = $keys[0];
        } else {
            throw new MisuseException('$thisValue must be not null');
        }

        $this->selfEntity = $selfModel;
        $this->selfFilter = $selfFilter;
        $this->selfValue = $entityManager->getReferencedKey($thatModel);
        $this->thatEntity = $thatModel;
        $this->thatField = $entityManager->getPrimaryKey($thatModel);
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        $selfFilter = $this->selfFilter;
        $thatField = $this->thatField;

        $ids = Arr::unique_column($r, $this->selfFilter);
        $repository = Container::get(EntityMetadataInterface::class)->getRepository($this->selfEntity);
        $pivotQuery = $repository->select([$this->selfFilter, $this->selfValue])->whereIn($this->selfFilter, $ids);
        $pivot_data = $pivotQuery->execute();
        $ids = Arr::unique_column($pivot_data, $this->selfValue);
        $data = $query->whereIn($this->thatField, $ids)->indexBy($this->thatField)->fetch();

        $rd = [];
        foreach ($pivot_data as $dv) {
            $key = $dv[$thatField];

            if (isset($data[$key])) {
                $rd[$dv[$selfFilter]][] = $data[$key];
            }
        }

        foreach ($r as $ri => $rv) {
            $rvr = $rv[$selfFilter];
            $r[$ri][$name] = $rd[$rvr] ?? [];
        }

        return $r;
    }

    public function lazyLoad(Entity $entity): QueryInterface
    {
        $selfFilter = $this->selfFilter;
        $selfRepository = Container::get(EntityMetadataInterface::class)->getRepository($this->selfEntity);
        $ids = $selfRepository->values($this->selfValue, [$selfFilter => $entity->$selfFilter]);
        $thatRepository = Container::get(EntityMetadataInterface::class)->getRepository($this->thatEntity);
        return $thatRepository->select()->whereIn($this->thatField, $ids)->setFetchType(true);
    }
}
