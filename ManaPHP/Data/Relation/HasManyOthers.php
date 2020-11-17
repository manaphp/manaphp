<?php

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\Relation;
use ManaPHP\Helper\Arr;

class HasManyOthers extends Relation
{
    /**
     * @var string
     */
    protected $_thisFilter;

    /**
     * @var string
     */
    protected $_thisValue;

    /**
     * @var string
     */
    protected $_thatField;

    /**
     * @param string $thisModel
     * @param string $thisFilter
     * @param string $thisValue
     * @param string $thatModel
     * @param string $thatField
     */
    public function __construct($thisModel, $thisFilter, $thisValue, $thatModel, $thatField)
    {
        $this->_thisModel = $thisModel;
        $this->_thisFilter = $thisFilter;
        $this->_thisValue = $thisValue;
        $this->_thatModel = $thatModel;
        $this->_thatField = $thatField;
    }

    /**
     * @param array                        $r
     * @param \ManaPHP\Data\QueryInterface $query
     * @param string                       $name
     * @param bool                         $asArray
     *
     * @return array
     */
    public function earlyLoad($r, $query, $name, $asArray)
    {
        /** @var \ManaPHP\Data\Model $thisModel */
        $thisModel = $this->_thisModel;
        $thisFilter = $this->_thisFilter;
        $thatField = $this->_thatField;

        $ids = Arr::unique_column($r, $this->_thisFilter);
        $pivotQuery = $thisModel::select([$this->_thisFilter, $this->_thisValue])->whereIn($this->_thisFilter, $ids);
        $pivot_data = $pivotQuery->execute();
        $ids = Arr::unique_column($pivot_data, $this->_thisValue);
        $data = $query->whereIn($this->_thatField, $ids)->indexBy($this->_thatField)->fetch($asArray);

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
     * @param \ManaPHP\Data\Model $instance
     *
     * @return \ManaPHP\Data\QueryInterface
     */
    public function lazyLoad($instance)
    {
        /** @var \ManaPHP\Data\Model $thatModel */
        /** @var \ManaPHP\Data\Model $thisModel */
        $thatModel = $this->_thatModel;
        $thisModel = $this->_thisModel;
        $thisFilter = $this->_thisFilter;

        $ids = $thisModel::values($this->_thisValue, [$thisFilter => $instance->$thisFilter]);

        return $thatModel::select()->whereIn($this->_thatField, $ids)->setFetchType(true);
    }
}
