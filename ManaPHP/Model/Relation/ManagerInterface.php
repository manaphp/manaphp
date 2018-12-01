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
     * @param bool           $asArray
     *
     * @return array
     */
    public function earlyLoad($model, $r, $withs, $asArray);

    /**
     * @param \ManaPHP\Model $instance
     * @param string         $relation_name
     *
     * @return \ManaPHP\QueryInterface
     */
    public function lazyLoad($instance, $relation_name);

    /**
     * @param \ManaPHP\Model          $model
     * @param                         $name
     * @param                         $data
     *
     * @return \ManaPHP\QueryInterface
     */
    public function getQuery($model, $name, $data);
}
