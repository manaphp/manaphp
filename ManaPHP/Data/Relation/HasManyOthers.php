<?php
declare(strict_types=1);

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\AbstractRelation;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Helper\Arr;

class HasManyOthers extends AbstractRelation
{
    protected string $thisFilter;
    protected string $thisValue;
    protected string $thatField;

    public function __construct(string $thisModel, string $thisFilter, string $thisValue, string $thatModel,
        string $thatField
    ) {
        $this->thisModel = $thisModel;
        $this->thisFilter = $thisFilter;
        $this->thisValue = $thisValue;
        $this->thatModel = $thatModel;
        $this->thatField = $thatField;
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        /** @var \ManaPHP\Data\ModelInterface $thisModel */
        $thisModel = $this->thisModel;
        $thisFilter = $this->thisFilter;
        $thatField = $this->thatField;

        $ids = Arr::unique_column($r, $this->thisFilter);
        $pivotQuery = $thisModel::select([$this->thisFilter, $this->thisValue])->whereIn($this->thisFilter, $ids);
        $pivot_data = $pivotQuery->execute();
        $ids = Arr::unique_column($pivot_data, $this->thisValue);
        $data = $query->whereIn($this->thatField, $ids)->indexBy($this->thatField)->fetch();

        $rd = [];
        foreach ($pivot_data as $dv) {
            $key = $dv[$thatField];

            if (isset($data[$key])) {
                $rd[$dv[$thisFilter]][] = $data[$key];
            }
        }

        foreach ($r as $ri => $rv) {
            $rvr = $rv[$thisFilter];
            $r[$ri][$name] = $rd[$rvr] ?? [];
        }

        return $r;
    }

    public function lazyLoad(ModelInterface $instance): QueryInterface
    {
        /** @var \ManaPHP\Data\ModelInterface $thatModel */
        /** @var \ManaPHP\Data\ModelInterface $thisModel */
        $thatModel = $this->thatModel;
        $thisModel = $this->thisModel;
        $thisFilter = $this->thisFilter;

        $ids = $thisModel::values($this->thisValue, [$thisFilter => $instance->$thisFilter]);

        return $thatModel::select()->whereIn($this->thatField, $ids)->setFetchType(true);
    }
}
