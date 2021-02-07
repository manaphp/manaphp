<?php

namespace ManaPHP\Data\Model\Expression;

use ManaPHP\Data\Db\Model as DbModel;
use ManaPHP\Data\Model\ExpressionInterface;

class Increment implements ExpressionInterface
{
    /**
     * @var float|int
     */
    protected $step;

    /**
     * @param int|float $step
     */
    public function __construct($step = 1)
    {
        $this->step = $step;
    }

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     * @param string                       $field
     *
     * @return array
     */
    public function compile($model, $field)
    {
        $step = $this->step;

        if ($model instanceof DbModel) {
            return ["[$field]=[$field]" . ($step >= 0 ? '+' : '-') . abs($step)];
        } else {
            return ['$inc' => [$field => $step]];
        }
    }
}