<?php

namespace ManaPHP\Model\Expression;

use ManaPHP\Db\Model as DbModel;
use ManaPHP\Model\ExpressionInterface;

class Raw implements ExpressionInterface
{
    /**
     * @var string|array
     */
    protected $_expression;

    /**
     * Raw constructor.
     *
     * @param string|array $expression
     */
    public function __construct($expression)
    {
        $this->_expression = $expression;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     * @param string                  $field
     *
     * @return array
     */
    public function compile($model, $field)
    {
        $expression = $this->_expression;

        if ($model instanceof DbModel) {
            return ["[$field]=$expression"];
        } else {
            return $expression;
        }
    }
}