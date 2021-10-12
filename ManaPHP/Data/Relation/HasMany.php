<?php

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\AbstractRelation;
use ManaPHP\Exception\MisuseException;

class HasMany extends AbstractRelation
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
     * @param string $thisModel
     * @param string $thisField
     * @param string $thatModel
     * @param string $thatField
     */
    public function __construct($thisModel, $thisField, $thatModel, $thatField)
    {
        $this->thisModel = $thisModel;
        $this->thisField = $thisField;
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
        $thisField = $this->thisField;
        $thatField = $this->thatField;

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
     * @param \ManaPHP\Data\ModelInterface $instance
     *
     * @return \ManaPHP\Data\QueryInterface
     */
    public function lazyLoad($instance)
    {
        /** @var \ManaPHP\Data\ModelInterface $thatModel */
        $thatModel = $this->thatModel;
        $thisField = $this->thisField;

        return $thatModel::select()->whereEq($this->thatField, $instance->$thisField)->setFetchType(true);
    }
}
