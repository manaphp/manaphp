<?php

namespace ManaPHP\Data\Model\Expression;

use ManaPHP\Data\Db\Model as DbModel;
use ManaPHP\Data\Model\ExpressionInterface;

class Raw implements ExpressionInterface
{
    /**
     * @var string|array
     */
    protected $expression;

    /**
     * @param string|array $expression
     */
    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     * @param string                       $field
     *
     * @return array|string
     */
    public function compile($model, $field)
    {
        $expression = $this->expression;

        if ($model instanceof DbModel) {
            return ["[$field]=$expression"];
        } else {
            return $expression;
        }
    }
}