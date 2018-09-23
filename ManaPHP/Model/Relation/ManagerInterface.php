<?php

namespace ManaPHP\Model\Relation;

interface ManagerInterface
{
    /**
     * @param \ManaPHP\Model $model
     * @param string         $name
     *
     * @return bool
     */
    public function has($model, $name);

    /**
     * @param \ManaPHP\Model $model
     * @param string         $name
     *
     * @return \ManaPHP\Model\Relation|false
     */
    public function get($model, $name);

    /**
     * @param \ManaPHP\Model $model
     * @param array          $r
     * @param array          $withs
     *
     * @return array
     */
    public function bulkPlainBind($model, $r, $withs);
}
