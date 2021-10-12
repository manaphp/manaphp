<?php

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\AbstractRelation;
use ManaPHP\Helper\Arr;

class HasManyOthers extends AbstractRelation
{
    /**
     * @var string
     */
    protected $thisFilter;

    /**
     * @var string
     */
    protected $thisValue;

    /**
     * @var string
     */
    protected $thatField;

    /**
     * @param string $thisModel
     * @param string $thisFilter
     * @param string $thisValue
     * @param string $thatModel
     * @param string $thatField
     */
    public function __construct($thisModel, $thisFilter, $thisValue, $thatModel, $thatField)
    {
        $this->thisModel = $thisModel;
        $this->thisFilter = $thisFilter;
        $this->thisValue = $thisValue;
        $this->thatModel = $thatModel;
        $this->thatField = $thatField;
    }

    /**
     * @param array                        $r
     * @param \ManaPHP\Data\QueryInterface $query
     * @param string                       $name
     *
     * @return array
     */
    public function earlyLoad($r, $query, $name)
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

    /**
     * @param \ManaPHP\Data\ModelInterface $instance
     *
     * @return \ManaPHP\Data\QueryInterface
     */
    public function lazyLoad($instance)
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
