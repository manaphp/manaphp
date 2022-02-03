<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

use ManaPHP\Data\ModelInterface;

interface ExpressionInterface
{
    public function compile(ModelInterface $model, string $field): string|array;
}