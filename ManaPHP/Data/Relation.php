<?php

namespace ManaPHP\Data;

abstract class Relation
{
    /**
     * @var string
     */
    protected $_thisModel;

    /**
     * @var string
     */
    protected $_thatModel;

    /**
     * @return \ManaPHP\Data\QueryInterface
     */
    public function getThatQuery()
    {
        /** @var \ManaPHP\Data\Model $referenceModel */
        $referenceModel = $this->_thatModel;

        return $referenceModel::select();
    }

    /**
     * @param array                        $r
     * @param \ManaPHP\Data\QueryInterface $query
     * @param string                       $name
     *
     * @return array
     */
    abstract public function earlyLoad($r, $query, $name);

    /**
     * @param \ManaPHP\Data\Model $instance
     *
     * @return \ManaPHP\Data\QueryInterface
     */
    abstract public function lazyLoad($instance);
}