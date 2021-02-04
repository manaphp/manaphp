<?php

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\Relation;
use ManaPHP\Exception\MisuseException;

class HasMany extends Relation
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

        $r_index = [];
        foreach ($r as $ri => $rv) {
            $r_index[$rv[$thisField]] = $ri;
        }

        $ids = array_column($r, $thisField);
        $data = $query->whereIn($thatField, $ids)->fetch();

        if (isset($data[0]) && !isset($data[0][$thatField])) {
            throw new MisuseException(['missing `%s` field in `%s` with', $thatField, $name]);
        }

        $rd = [];
        foreach ($data as $dv) {
            $rd[$r_index[$dv[$thatField]]][] = $dv;
        }

        foreach ($r as $ri => $rv) {
            $r[$ri][$name] = $rd[$ri] ?? [];
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

        return $thatModel::select()->whereEq($this->_thatField, $instance->$thisField)->setFetchType(true);
    }
}
