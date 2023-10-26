<?php
declare(strict_types=1);

namespace ManaPHP\Model\Relation;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Container;
use ManaPHP\Model\AbstractRelation;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Model\ModelsInterface;
use ManaPHP\Query\QueryInterface;

class HasManyOthers extends AbstractRelation
{
    protected string $selfFilter;
    protected string $selfValue;
    protected string $thatField;

    public function __construct(string $selfModel, string $thatModel)
    {
        $models = Container::get(ModelsInterface::class);
        $referencedKey = $models->getReferencedKey($thatModel);

        $keys = [];
        foreach ($models->getFields($selfModel) as $field) {
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

        $this->selfModel = $selfModel;
        $this->selfFilter = $selfFilter;
        $this->selfValue = $models->getReferencedKey($thatModel);
        $this->thatModel = $thatModel;
        $this->thatField = $models->getPrimaryKey($thatModel);
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        /** @var ModelInterface $selfModel */
        $selfModel = $this->selfModel;
        $selfFilter = $this->selfFilter;
        $thatField = $this->thatField;

        $ids = Arr::unique_column($r, $this->selfFilter);
        $pivotQuery = $selfModel::select([$this->selfFilter, $this->selfValue])->whereIn($this->selfFilter, $ids);
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

    public function lazyLoad(ModelInterface $instance): QueryInterface
    {
        /** @var ModelInterface $thatModel */
        /** @var ModelInterface $selfModel */
        $thatModel = $this->thatModel;
        $selfModel = $this->selfModel;
        $selfFilter = $this->selfFilter;

        $ids = $selfModel::values($this->selfValue, [$selfFilter => $instance->$selfFilter]);

        return $thatModel::select()->whereIn($this->thatField, $ids)->setFetchType(true);
    }
}
