<?php
namespace ManaPHP\Model\Relation;

use ManaPHP\Helper\Arr;
use ManaPHP\Model\Relation;

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
     * Relation constructor.
     *
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
     * @param array                   $r
     * @param \ManaPHP\QueryInterface $query
     * @param string                  $name
     * @param bool                    $asArray
     *
     * @return array
     */
    public function earlyLoad($r, $query, $name, $asArray)
    {
        /** @var \ManaPHP\Model $thisModel */
        $thisModel = $this->_thisModel;
        $thisFilter = $this->_thisFilter;
        $thatField = $this->_thatField;

        $ids = Arr::unique_column($r, $this->_thisFilter);
        $pivot_data = $thisModel::select([$this->_thisFilter, $this->_thisValue])->whereIn($this->_thisFilter, $ids)->execute();
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
     * @param \ManaPHP\Model $instance
     *
     * @return \ManaPHP\QueryInterface
     */
    public function lazyLoad($instance)
    {
        /** @var \ManaPHP\Model $thatModel */
        /** @var \ManaPHP\Model $thisModel */
        $thatModel = $this->_thatModel;
        $thisModel = $this->_thisModel;
        $thisFilter = $this->_thisFilter;

        $ids = $thisModel::values($this->_thisValue, [$thisFilter => $instance->$thisFilter]);

        return $thatModel::select()->whereIn($this->_thatField, $ids)->setFetchType(true);
    }
}
