<?php

namespace ManaPHP\Model;

interface ExpressionInterface
{
    /**
     * @param \ManaPHP\ModelInterface $model
     * @param string                  $field
     *
     * @return string|array
     */
    public function compile($model, $field);
}