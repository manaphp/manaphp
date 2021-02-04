<?php

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\Relation;

class HasOne extends Relation
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
     * @param string $thisModel
     * @param string $thisField
     * @param string $thatModel
     * @param string $thatField
     */
    public function __construct($thisModel, $thisField, $thatModel, $thatField)
    {
        $this->_thisModel = $thisModel;
        $this->_thisField = $thisField;
        $this->_thatModel = $thatModel;
        $this->_thatField = $thatField;
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
        $thisField = $this->_thisField;
        $thatField = $this->_thatField;

        $ids = array_values(array_unique(array_column($r, $thisField)));
        $data = $query->whereIn($thatField, $ids)->indexBy($thatField)->fetch();

        foreach ($r as $ri => $rv) {
            $key = $rv[$thisField];
            $r[$ri][$name] = $data[$key] ?? null;
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
        $thatModel = $this->_thatModel;
        $thisField = $this->_thisField;
        $thatField = $this->_thatField;

        return $thatModel::select()->whereEq($thatField, $instance->$thisField)->setFetchType(false);
    }
}
