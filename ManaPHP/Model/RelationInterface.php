<?php
namespace ManaPHP\Model;

interface RelationInterface
{
    /**
     * @param \ManaPHP\Model $model
     *
     * @return \ManaPHP\Model\CriteriaInterface
     */
    public function criteria($model);
}