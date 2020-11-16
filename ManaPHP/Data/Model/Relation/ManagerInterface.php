<?php

namespace ManaPHP\Data\Model\Relation;

interface ManagerInterface
{
    /**
     * @param \ManaPHP\Data\Model $model
     * @param string              $name
     *
     * @return bool
     */
    public function has($model, $name);

    /**
     * @param \ManaPHP\Data\Model $model
     * @param string              $name
     *
     * @return \ManaPHP\Data\Model\Relation|false
     */
    public function get($model, $name);

    /**
     * @param \ManaPHP\Data\Model $model
     * @param array               $r
     * @param array               $withs
     * @param bool                $asArray
     *
     * @return array
     */
    public function earlyLoad($model, $r, $withs, $asArray);

    /**
     * @param \ManaPHP\Data\Model $instance
     * @param string              $relation_name
     *
     * @return \ManaPHP\Data\QueryInterface
     */
    public function lazyLoad($instance, $relation_name);

    /**
     * @param \ManaPHP\Data\Model     $model
     * @param                         $name
     * @param                         $data
     *
     * @return \ManaPHP\Data\QueryInterface
     */
    public function getQuery($model, $name, $data);
}
