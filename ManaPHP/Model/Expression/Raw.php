<?php
namespace ManaPHP\Model\Expression;

use ManaPHP\Model\ExpressionInterface;

class Raw implements ExpressionInterface
{
    /**
     * @var string|array
     */
    public $expression;

    /**
     * Raw constructor.
     *
     * @param string|array $expression
     */
    public function __construct($expression)
    {
        $this->expression = $expression;
    }
}