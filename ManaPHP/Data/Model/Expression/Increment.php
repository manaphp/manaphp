<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model\Expression;

use ManaPHP\Data\Db\Model as DbModel;
use ManaPHP\Data\Model\ExpressionInterface;
use ManaPHP\Data\ModelInterface;

class Increment implements ExpressionInterface
{
    protected float|int $step;

    public function __construct(int|float $step = 1)
    {
        $this->step = $step;
    }

    public function compile(ModelInterface $model, string $field): array
    {
        $step = $this->step;

        if ($model instanceof DbModel) {
            return ["[$field]=[$field]" . ($step >= 0 ? '+' : '-') . abs($step)];
        } else {
            return ['$inc' => [$field => $step]];
        }
    }
}