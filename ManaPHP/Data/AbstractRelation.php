<?php

namespace ManaPHP\Data;

abstract class AbstractRelation
{
    /**
     * @var string
     */
    protected $thisModel;

    /**
     * @var string
     */
    protected $thatModel;

    /**
     * @return \ManaPHP\Data\QueryInterface
     */
    public function getThatQuery()
    {
        /** @var \ManaPHP\Data\ModelInterface $referenceModel */
        $referenceModel = $this->thatModel;

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
     * @param \ManaPHP\Data\ModelInterface $instance
     *
     * @return \ManaPHP\Data\QueryInterface
     */
    abstract public function lazyLoad($instance);
}