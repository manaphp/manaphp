<?php
declare(strict_types=1);

namespace ManaPHP\Data\Merger;

interface FieldsInfererInterface
{
    public function fields(AbstractModel $model): array;
}