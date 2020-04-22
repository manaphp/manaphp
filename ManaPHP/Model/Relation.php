<?php

namespace ManaPHP\Model;

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
     * @return \ManaPHP\QueryInterface
     */
    public function getThatQuery()
    {
        /** @var \ManaPHP\Model $referenceModel */
        $referenceModel = $this->_thatModel;

        return $referenceModel::select();
    }

    /**
     * @param array                   $r
     * @param \ManaPHP\QueryInterface $query
     * @param string                  $name
     * @param bool                    $asArray
     *
     * @return array
     */
    abstract public function earlyLoad($r, $query, $name, $asArray);

    /**
     * @param \ManaPHP\Model $instance
     *
     * @return \ManaPHP\QueryInterface
     */
    abstract public function lazyLoad($instance);
}