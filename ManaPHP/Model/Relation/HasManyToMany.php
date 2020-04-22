<?php

namespace ManaPHP\Model\Relation;

use ManaPHP\Helper\Arr;
use ManaPHP\Model\Relation;

class HasManyToMany extends Relation
{
    /**
     * @var string
     */
    protected $_thisField;

    /**
     * @var string
     */
    protected $_thatField;

    /**
     * @var string
     */
    protected $_pivotModel;

    /**
     * @var string
     */
    protected $_thisPivot;

    /**
     * @var string
     */
    protected $_thatPivot;

    /**
     * Relation constructor.
     *
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
        $this->_thisModel = $thisModel;
        $this->_thisField = $thisField;
        $this->_thatModel = $thatModel;
        $this->_thatField = $thatField;
        $this->_pivotModel = $pivotModel;
        $this->_thisPivot = $thisPivot;
        $this->_thatPivot = $thatPivot;
    }

    /**
     * @param array                   $r
     * @param \ManaPHP\QueryInterface $query
     * @param string                  $name
     * @param bool                    $asArray
     *
     * @return array
     */
    public function earlyLoad($r, $query, $name, $asArray)
    {
        /** @var \ManaPHP\ModelInterface $pivotModel */
        $pivotModel = $this->_pivotModel;
        $thisPivot = $this->_thisPivot;
        $thatPivot = $this->_thatPivot;

        $ids = Arr::unique_column($r, $this->_thisField);
        $pivot_data = $pivotModel::select([$this->_thisPivot, $this->_thatPivot])->whereIn($this->_thisPivot, $ids)->execute();
        $ids = Arr::unique_column($pivot_data, $this->_thatPivot);
        $data = $query->whereIn($this->_thatField, $ids)->indexBy($this->_thatField)->fetch($asArray);

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
     * @param \ManaPHP\Model $instance
     *
     * @return \ManaPHP\QueryInterface
     */
    public function lazyLoad($instance)
    {
        /** @var \ManaPHP\Model $pivotModel */
        /** @var \ManaPHP\Model $thatModel */
        $thatModel = $this->_thatModel;
        $thisField = $this->_thisField;
        $pivotModel = $this->_pivotModel;

        $ids = $pivotModel::values($this->_thatPivot, [$this->_thisPivot => $instance->$thisField]);
        return $thatModel::select()->whereIn($this->_thatField, $ids)->setFetchType(true);
    }
}
