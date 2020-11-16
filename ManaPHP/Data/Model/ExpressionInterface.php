<?php

namespace ManaPHP\Data\Model;

interface ExpressionInterface
{
    /**
     * @param \ManaPHP\Data\ModelInterface $model
     * @param string                       $field
     *
     * @return string|array
     */
    public function compile($model, $field);
}