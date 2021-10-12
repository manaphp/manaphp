<?php

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\Relation;
use ManaPHP\Helper\Arr;

class HasManyToMany extends Relation
{
    /**
     * @var string
     */
    protected $thisField;

    /**
     * @var string
     */
    protected $thatField;

    /**
     * @var string
     */
    protected $pivotModel;

    /**
     * @var string
     */
    protected $thisPivot;

    /**
     * @var string
     */
    protected $thatPivot;

    /**
     * @param string $thisModel
     * @param string $thisField
     * @param string $thatModel
     * @param string $thatField
     * @param string $pivotModel
     * @param string $thisPivot
     * @param string $thatPivot
     */
    public function __construct($thisModel, $thisField, $thatModel, $thatField, $pivotModel, $thisPivot, $thatPivot)
    {
        $this->thisModel = $thisModel;
        $this->thisField = $thisField;
        $this->thatModel = $thatModel;
        $this->thatField = $thatField;
        $this->pivotModel = $pivotModel;
        $this->thisPivot = $thisPivot;
        $this->thatPivot = $thatPivot;
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
        /** @var \ManaPHP\Data\ModelInterface $pivotModel */
        $pivotModel = $this->pivotModel;
        $thisPivot = $this->thisPivot;
        $thatPivot = $this->thatPivot;

        $ids = Arr::unique_column($r, $this->thisField);
        $pivotQuery = $pivotModel::select([$this->thisPivot, $this->thatPivot])->whereIn($this->thisPivot, $ids);
        $pivot_data = $pivotQuery->execute();
        $ids = Arr::unique_column($pivot_data, $this->thatPivot);
        $data = $query->whereIn($this->thatField, $ids)->indexBy($this->thatField)->fetch();

        $rd = [];
        foreach ($pivot_data as $dv) {
            $key = $dv[$thatPivot];

            if (isset($data[$key])) {
                $rd[$dv[$thisPivot]][] = $data[$key];
            }
        }

        foreach ($r as $ri => $rv) {
            $rvr = $rv[$thisPivot];
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
        /** @var \ManaPHP\Data\ModelInterface $pivotModel */
        /** @var \ManaPHP\Data\ModelInterface $thatModel */
        $thatModel = $this->thatModel;
        $thisField = $this->thisField;
        $pivotModel = $this->pivotModel;

        $ids = $pivotModel::values($this->thatPivot, [$this->thisPivot => $instance->$thisField]);
        return $thatModel::select()->whereIn($this->thatField, $ids)->setFetchType(true);
    }
}
