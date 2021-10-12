<?php

namespace ManaPHP\Data\Relation;

interface ManagerInterface
{
    /**
     * @param \ManaPHP\Data\ModelInterface $model
     * @param string                       $name
     *
     * @return bool
     */
    public function has($model, $name);

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     * @param string                       $name
     *
     * @return \ManaPHP\Data\AbstractRelation|false
     */
    public function get($model, $name);

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     * @param array                        $r
     * @param array                        $withs
     *
     * @return array
     */
    public function earlyLoad($model, $r, $withs);

    /**
     * @param \ManaPHP\Data\ModelInterface $instance
     * @param string                       $relation_name
     *
     * @return \ManaPHP\Data\QueryInterface
     */
    public function lazyLoad($instance, $relation_name);

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     * @param                              $name
     * @param                              $data
     *
     * @return \ManaPHP\Data\QueryInterface
     */
    public function getQuery($model, $name, $data);
}
