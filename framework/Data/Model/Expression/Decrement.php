<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model\Expression;

use ManaPHP\Data\Db\Model as DbModel;
use ManaPHP\Data\Model\ExpressionInterface;
use ManaPHP\Data\ModelInterface;

class Decrement implements ExpressionInterface
{
    public int|float $_step;

    public function __construct(int|float $step = 1)
    {
        $this->_step = $step;
    }

    public function compile(ModelInterface $model, string $field): array
    {
        $step = $this->_step;

        if ($model instanceof DbModel) {
            return ["[$field]=[$field]" . ($step >= 0 ? '-' : '+') . abs($step)];
        } else {
            return ['$inc' => [$field => -$step]];
        }
    }
}