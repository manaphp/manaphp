<?php

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\Relation;

class BelongsTo extends Relation
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
        $thatModel = $this->thatModel;
        $thisField = $this->thisField;
        $thatField = $this->thatField;

        return $thatModel::select()->whereEq($thatField, $instance->$thisField)->setFetchType(false);
    }
}
