<?php
namespace ManaPHP\Model\Expression;

use ManaPHP\Model\ExpressionInterface;

class Decrement implements ExpressionInterface
{
    /**
     * @var float|int
     */
    public $step;

    public function __construct($step = 1)
    {
        $this->step = $step;
    }
}