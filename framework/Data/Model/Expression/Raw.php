<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model\Expression;

use ManaPHP\Data\Db\Model as DbModel;
use ManaPHP\Data\Model\ExpressionInterface;
use ManaPHP\Data\ModelInterface;

class Raw implements ExpressionInterface
{
    protected string|array $expression;

    public function __construct(string|array $expression)
    {
        $this->expression = $expression;
    }

    public function compile(ModelInterface $model, string $field): array
    {
        $expression = $this->expression;

        if ($model instanceof DbModel) {
            return ["[$field]=$expression"];
        } else {
            return $expression;
        }
    }
}