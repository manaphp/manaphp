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
    public function earlyLoad($model, $r, $withs);

    /**
     * @param \ManaPHP\Model $instance
     * @param array          $withs
     *
     * @return \ManaPHP\Model
     *
     * @throws \ManaPHP\Exception\InvalidValueException
     */
    public function lazyBindAll($instance, $withs);
}
