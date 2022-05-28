<?php
declare(strict_types=1);

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\AbstractRelation;
use ManaPHP\Data\Model\ManagerInterface;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Container;

class HasManyOthers extends AbstractRelation
{
    protected string $selfFilter;
    protected string $selfValue;
    protected string $thatField;

    public function __construct(string $selfModel, string $thatModel)
    {
        $modelManager = Container::get(ManagerInterface::class);
        $referencedKey = $modelManager->getReferencedKey($thatModel);

        $keys = [];
        foreach ($modelManager->getFields($selfModel) as $field) {
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
        $this->selfValue = $modelManager->getReferencedKey($thatModel);
        $this->thatModel = $thatModel;
        $this->thatField = $modelManager->getPrimaryKey($thatModel);
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        /** @var \ManaPHP\Data\ModelInterface $selfModel */
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
        /** @var \ManaPHP\Data\ModelInterface $thatModel */
        /** @var \ManaPHP\Data\ModelInterface $selfModel */
        $thatModel = $this->thatModel;
        $selfModel = $this->selfModel;
        $selfFilter = $this->selfFilter;

        $ids = $selfModel::values($this->selfValue, [$selfFilter => $instance->$selfFilter]);

        return $thatModel::select()->whereIn($this->thatField, $ids)->setFetchType(true);
    }
}
