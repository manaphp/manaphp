<?php

namespace ManaPHP\Model\Expression;

use ManaPHP\Db\Model as DbModel;
use ManaPHP\Model\ExpressionInterface;

class Increment implements ExpressionInterface
{
    /**
     * @var float|int
     */
    protected $_step;

    /**
     * Increment constructor.
     *
     * @param int|float $step
     */
    public function __construct($step = 1)
    {
        $this->_step = $step;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     * @param string                  $field
     *
     * @return array
     */
    public function compile($model, $field)
    {
        $step = $this->_step;

        if ($model instanceof DbModel) {
            return ["[$field]=[$field]" . ($step >= 0 ? '+' : '-') . abs($step)];
        } else {
            return ['$inc' => [$field => $step]];
        }
    }
}